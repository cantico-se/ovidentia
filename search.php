<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";
include $babInstallPath."utilit/forumincl.php";

$babLimit = 20;
function searchKeyword($sfaq, $sart, $snot, $sfor, $what)
	{
	global $body;

	class tempb
		{
		var $search;
		var $all;
		var $in;
		var $update;
		var $itemvalue;
		var $itemname;
		var $arr = array();
		var $sfaq;
		var $sart;
		var $snot;
		var $sfor;
		var $what;

		function tempb($sfaq, $sart, $snot, $sfor, $what)
			{
			$this->search = babTranslate("Search");
			$this->all = babTranslate("All");
			$this->in = babTranslate("in");
			$this->sfaq = $sfaq;
			$this->sart = $sart;
			$this->snot = $snot;
			$this->sfor = $sfor;
			$this->what = stripslashes($what);
			if( $sart == 1)
				$this->arr[] = "art";
			if( $sfor == 1)
				$this->arr[] = "for";
			if( $sfaq == 1)
				$this->arr[] = "faq";
			if( $snot == 1)
				$this->arr[] = "not";

			$this->count = count($this->arr);
			}

		function getnextitem()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->itemvalue = $this->arr[$i];
				$this->itemname = babTranslate($this->arr[$i]);
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$tempb = new tempb($sfaq, $sart, $snot, $sfor, $what);
	$body->babecho(	babPrintTemplate($tempb,"search.html", "search"));
	}

function startSearch($sfaq, $sart, $snot, $sfor, $item, $what, $pos)
	{
	global $body;

	class temp
		{
		var $what;
		var $search;
		var $db;
		var $arttitle;
		var $comtitle;
		var $fortitle;
		var $faqtitle;
		var $nottitle;

		function temp($sfaq, $sart, $snot, $sfor, $item, $what, $pos)
			{
			global $BAB_SESS_USERID, $babLimit;

			$this->db = new db_mysql();
			$this->search = babTranslate("Search");
			$this->arttitle = babTranslate("Articles");
			$this->comtitle = babTranslate("Comments");
			$this->fortitle = babTranslate("Posts");
			$this->faqtitle = babTranslate("Faq");
			$this->nottitle = babTranslate("Notes");
			$this->next = babTranslate( "Next" );

			$this->what = $what;
			$this->countart = 0;
			$this->countfor = 0;
			$this->countnot = 0;
			$this->countfaq = 0;
			$this->countcom = 0;
			if( empty($item) || $item == "art")
				{
				$req = "create temporary table artresults select id, id_topic, title from articles where 0";
				$this->db->db_query($req);
				$req = "alter table artresults add unique (id)";
				$this->db->db_query($req);

				$req = "create temporary table comresults select id, id_article, id_topic, subject from comments where 0";
				$this->db->db_query($req);
				$req = "alter table comresults add unique (id)";
				$this->db->db_query($req);

				$req = "select id from topics";
				$res = $this->db->db_query($req);
				while( $row = $this->db->db_fetch_array($res))
					{
					if(isAccessValid("topicsview_groups", $row[id]))
						{
						$req = "insert into artresults select id, id_topic, title from articles where title like '%".$what."%' and confirmed='Y' and id_topic='".$row[id]."'";
						$this->db->db_query($req);

						$req = "insert into artresults select id, id_topic, title from articles where head like '%".$what."%' and confirmed='Y' and id_topic='".$row[id]."'";
						$this->db->db_query($req);

						$req = "insert into artresults select id, id_topic, title from articles where body like '%".$what."%' and confirmed='Y' and id_topic='".$row[id]."'";
						$this->db->db_query($req);

						$req = "insert into comresults select id, id_article, id_topic, subject from comments where subject like '%".$what."%' and confirmed='Y' and id_topic='".$row[id]."'";
						$this->db->db_query($req);

						$req = "insert into comresults select id, id_article, id_topic, subject from comments where message like '%".$what."%' and confirmed='Y' and id_topic='".$row[id]."'";
						$this->db->db_query($req);
						}
					}

				$req = "select count(*) from artresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				$req = "select * from artresults limit ".$pos.", ".$babLimit;
				$this->resart = $this->db->db_query($req);
				$this->countart = $this->db->db_num_rows($this->resart);

				if( $pos + $babLimit < $nbrows )
					{
					$this->artpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->artnext = $GLOBALS[babUrl]."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&sart=".$sart."&sfaq=".$sfaq."&snot=".$snot."&sfor=".$sfor."&what=".urlencode($what);
					}
				else
					{
					$this->artpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->artnext = 0;
					}

				$req = "select count(*) from comresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->compage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->comnext = $GLOBALS[babUrl]."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&sart=".$sart."&sfaq=".$sfaq."&snot=".$snot."&sfor=".$sfor."&what=".urlencode($what);
					}
				else
					{
					$this->compage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->comnext = 0;
					}

				$req = "select * from comresults limit ".$pos.", ".$babLimit;
				$this->rescom = $this->db->db_query($req);
				$this->countcom = $this->db->db_num_rows($this->rescom);
				}

			if( empty($item) || $item == "for")
				{
				//http://dev.ovidentia.org/index.php?tg=posts&idx=List&forum=6&thread=45&post=141
				$req = "create temporary table forresults select id, id_thread, subject from posts where 0";
				$this->db->db_query($req);
				$req = "alter table forresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from forums";
				$res = $this->db->db_query($req);
				while( $row = $this->db->db_fetch_array($res))
					{
					if(isAccessValid("forumsview_groups", $row[id]))
						{
						$req = "select id from threads where forum='".$row[id]."'";
						$res2 = $this->db->db_query($req);
						while( $r = $this->db->db_fetch_array($res2))
							{
							$req = "insert into forresults select id, id_thread, subject from posts where subject like '%".$what."%' and confirmed='Y' and id_thread='".$r[id]."'";
							$this->db->db_query($req);

							$req = "insert into forresults select id, id_thread, subject from posts where message like '%".$what."%' and confirmed='Y' and id_thread='".$r[id]."'";
							$this->db->db_query($req);
							}
						}
					}

				$req = "select count(*) from forresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->forpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->fornext = $GLOBALS[babUrl]."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&sart=".$sart."&sfaq=".$sfaq."&snot=".$snot."&sfor=".$sfor."&what=".urlencode($what);
					}
				else
					{
					$this->forpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->fornext = 0;
					}

				$req = "select * from forresults limit ".$pos.", ".$babLimit;
				$this->resfor = $this->db->db_query($req);
				$this->countfor = $this->db->db_num_rows($this->resfor);
				}

			if( empty($item) || $item == "faq")
				{
				$req = "create temporary table faqresults select * from faqqr where 0";
				$this->db->db_query($req);
				$req = "alter table faqresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from faqcat";
				$res = $this->db->db_query($req);
				while( $row = $this->db->db_fetch_array($res))
					{
					if(isAccessValid("faqcat_groups", $row[id]))
						{
						$req = "insert into faqresults select * from faqqr where question like '%".$what."%' and idcat='".$row[id]."'";
						$this->db->db_query($req);

						$req = "insert into faqresults select * from faqqr where response like '%".$what."%' and idcat='".$row[id]."'";
						$this->db->db_query($req);
						}
					}

				$req = "select count(*) from faqresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->faqpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->faqnext = $GLOBALS[babUrl]."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&sart=".$sart."&sfaq=".$sfaq."&snot=".$snot."&sfor=".$sfor."&what=".urlencode($what);
					}
				else
					{
					$this->faqpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->faqnext = 0;
					}

				$req = "select * from faqresults limit ".$pos.", ".$babLimit;
				$this->resfaq = $this->db->db_query($req);
				$this->countfaq = $this->db->db_num_rows($this->resfaq);
				}
			
			if( (empty($item) || $item == "not") && !empty($BAB_SESS_USERID))
				{
				$req = "select count(*) from notes where content like '%".$what."%' and id_user='".$BAB_SESS_USERID."'";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->notpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->notnext = $GLOBALS[babUrl]."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&sart=".$sart."&sfaq=".$sfaq."&snot=".$snot."&sfor=".$sfor."&what=".urlencode($what);
					}
				else
					{
					$this->notpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->notnext = 0;
					}

				$req = "select * from notes where content like '%".$what."%' and id_user='".$BAB_SESS_USERID."' limit ".$pos.", ".$babLimit;
				$this->resnot = $this->db->db_query($req);
				$this->countnot = $this->db->db_num_rows($this->resnot);
				}

			}

		function getnextart()
			{
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $this->db->db_fetch_array($this->resart);
				$this->article = $arr[title];
				$this->articleurl = "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=search&idx=a&id=".$arr[id]."&w=".$this->what."')";
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists artresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextcom()
			{
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $this->db->db_fetch_array($this->rescom);
				$this->com = $arr[subject];
				$this->comurl = "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=search&idx=c&idt=".$arr[id_topic]."&ida=".$arr[id_article]."&idc=".$arr[id]."')";
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists comresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextfor()
			{
			static $i = 0;
			if( $i < $this->countfor)
				{
				$arr = $this->db->db_fetch_array($this->resfor);
				$this->post = $arr[subject];
				$this->posturl = "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=search&idx=f&idt=".$arr[id_thread]."&idp=".$arr[id]."')";
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists forresults";
				$this->db->db_query($req);
				return false;
				}
			}
		function getnextfaq()
			{
			static $i = 0;
			if( $i < $this->countfaq)
				{
				$arr = $this->db->db_fetch_array($this->resfaq);
				$this->question = $arr[question];
				$this->questionurl = "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=search&idx=q&idc=".$arr[idcat]."&idq=".$arr[id]."')";
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists faqresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextnot()
			{
			static $i = 0;
			if( $i < $this->countnot)
				{
				$arr = $this->db->db_fetch_array($this->resnot);
				$this->content = $arr[content];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new temp($sfaq, $sart, $snot, $sfor, $item, $what, $pos);
	$body->babecho(	babPrintTemplate($temp,"search.html", "searchresult"));
	}

function viewArticle($article, $w)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $title;
		var $style;
		var $babUrl;
		var $sitename;

		function temp($article, $w)
			{
			$this->style = $GLOBALS[babStyle];
			$this->babUrl = $GLOBALS[babUrl];
			$this->sitename = $GLOBALS[babSiteName];
			$db = new db_mysql();
			$req = "select * from articles where id='$article' and confirmed='Y'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->content = $arr[body];
			$this->title = $arr[title];
			}
		}
	
	$temp = new temp($article, $w);
	echo babPrintTemplate($temp,"search.html", "viewart");
	}

function viewComment($topics, $article, $com)
	{
	global $body;
	
	class ctp
		{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();
		var $style;
		var $babUrl;
		var $sitename;

		function ctp($topics, $article, $com)
			{
			$this->style = $GLOBALS[babStyle];
			$this->babUrl = $GLOBALS[babUrl];
			$this->sitename = $GLOBALS[babSiteName];
			$this->title = getArticleTitle($article);
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
	echo babPrintTemplate($ctp,"search.html", "viewcom");
	}

function viewPost($thread, $post)
	{
	global $body;

	class temp
		{
	
		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $style;
		var $babUrl;
		var $sitename;

		function temp($thread, $post)
			{
			$this->style = $GLOBALS[babStyle];
			$this->babUrl = $GLOBALS[babUrl];
			$this->sitename = $GLOBALS[babSiteName];
			$db = new db_mysql();
			$req = "select forum from threads where id='".$thread."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			$this->title = getForumName($arr[forum]);
			$req = "select * from posts where id='".$post."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			$this->postdate = bab_strftime(bab_mktime($arr[date]));
			$this->postauthor = $arr[author];
			$this->postsubject = $arr[subject];
			$this->postmessage = $arr[message];
			}
		}
	
	$temp = new temp($thread, $post);
	echo babPrintTemplate($temp,"search.html", "viewfor");
	}

function viewQuestion($idcat, $id)
	{
	global $body;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $style;
		var $babUrl;
		var $sitename;

		function temp($idcat, $id)
			{
			$this->style = $GLOBALS[babStyle];
			$this->babUrl = $GLOBALS[babUrl];
			$this->sitename = $GLOBALS[babSiteName];
			$this->db = new db_mysql();
			$req = "select * from faqqr where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$req = "select category from faqcat where id='$idcat'";
			$a = $this->db->db_fetch_array($this->db->db_query($req));
			$this->title = $a[category];
			}

		}

	$temp = new temp($idcat, $id);
	echo babPrintTemplate($temp,"search.html", "viewfaq");
	return true;
	}

if( !isset($pos))
	$pos = 0;

if( !isset($what))
	$what = "";

switch($idx)
	{
	case "a":
		viewArticle($id, $w);
		exit;
		break;

	case "c":
		viewComment($idt, $ida, $idc);
		exit;
		break;

	case "f":
		viewPost($idt, $idp);
		exit;
		break;

	case "q":
		viewQuestion($idc, $idq);
		exit;
		break;

	case "find":
		$body->title = babTranslate("Search");
		searchKeyword($sfaq, $sart, $snot, $sfor, $what);
		startSearch($sfaq, $sart, $snot, $sfor, $item, $what, $pos);
		break;

	default:
		$body->title = babTranslate("Search");
		searchKeyword($sfaq, $sart, $snot, $sfor, $what);
		break;
	}

$body->setCurrentItemMenu($idx);
?>