<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";
include $babInstallPath."utilit/forumincl.php";

$babLimit = 20;

function highlightWord( $w, $text)
{
	return preg_replace("/(\s*>[^<]*|\s+)(".$w.")(\s+|[^>]*<\s*)/si", "\\1<span class=\"Babhighlight\">\\2</span>\\3", $text);
}

function searchKeyword($pat, $what)
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
		var $sfil;
		var $what;

		function tempb($pat, $what)
			{
			global $babSearchItems;
			$this->search = babTranslate("Search");
			$this->all = babTranslate("All");
			$this->in = babTranslate("in");
			$this->pat = $pat;
			$this->what = stripslashes($what);

			foreach ($babSearchItems as $key => $value)
				{
				if( substr_count($pat, $key))
					$this->arr[] = $key;
				}
			$this->count = count($this->arr);
			}

		function getnextitem()
			{
			global $babSearchItems;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->itemvalue = $this->arr[$i];
				$this->itemname = babTranslate($babSearchItems[$this->arr[$i]]);
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$tempb = new tempb($pat, $what);
	$body->babecho(	babPrintTemplate($tempb,"search.html", "search"));
	}

function startSearch($pat, $item, $what, $pos)
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
		var $filtitle;
		var $contitle;

		function temp($pat, $item, $what, $pos)
			{
			global $BAB_SESS_USERID, $babLimit;

			$this->db = new db_mysql();
			$this->search = babTranslate("Search");
			$this->arttitle = babTranslate("Articles");
			$this->comtitle = babTranslate("Comments");
			$this->fortitle = babTranslate("Posts");
			$this->faqtitle = babTranslate("Faq");
			$this->nottitle = babTranslate("Notes");
			$this->filtitle = babTranslate("Files");
			$this->contitle = babTranslate("Contacts");
			$this->next = babTranslate( "Next" );

			//$this->like = "not regexp '<.*".$what."[^>]*'";
			$this->like = "like '%".$what."%'";
			$this->what = urlencode($what);
			$this->countart = 0;
			$this->countfor = 0;
			$this->countnot = 0;
			$this->countfaq = 0;
			$this->countcom = 0;
			$this->countfil = 0;
			$this->countcon = 0;
			if( empty($item) || $item == "a")
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
					if(isAccessValid("topicsview_groups", $row['id']))
						{
						$req = "insert into artresults select id, id_topic, title from articles where title ".$this->like." and confirmed='Y' and id_topic='".$row['id']."'";
						$this->db->db_query($req);

						$req = "insert into artresults select id, id_topic, title from articles where head ".$this->like." and confirmed='Y' and id_topic='".$row['id']."'";
						$this->db->db_query($req);

						$req = "insert into artresults select id, id_topic, title from articles where body ".$this->like." and confirmed='Y' and id_topic='".$row['id']."'";
						$this->db->db_query($req);

						$req = "insert into comresults select id, id_article, id_topic, subject from comments where subject ".$this->like." and confirmed='Y' and id_topic='".$row['id']."'";
						$this->db->db_query($req);

						$req = "insert into comresults select id, id_article, id_topic, subject from comments where message ".$this->like." and confirmed='Y' and id_topic='".$row['id']."'";
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
					$this->artnext = $GLOBALS['babUrl']."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat".$pat."&what=".$this->what;
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
					$this->comnext = $GLOBALS['babUrl']."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat".$pat."&what=".$this->what;
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

			if( empty($item) || $item == "b")
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
					if(isAccessValid("forumsview_groups", $row['id']))
						{
						$req = "select id from threads where forum='".$row['id']."'";
						$res2 = $this->db->db_query($req);
						while( $r = $this->db->db_fetch_array($res2))
							{
							$req = "insert into forresults select id, id_thread, subject from posts where subject ".$this->like." and confirmed='Y' and id_thread='".$r['id']."'";
							$this->db->db_query($req);

							$req = "insert into forresults select id, id_thread, subject from posts where message ".$this->like." and confirmed='Y' and id_thread='".$r['id']."'";
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
					$this->fornext = $GLOBALS['babUrl']."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat".$pat."&what=".$this->what;
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

			if( empty($item) || $item == "c")
				{
				$req = "create temporary table faqresults select * from faqqr where 0";
				$this->db->db_query($req);
				$req = "alter table faqresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from faqcat";
				$res = $this->db->db_query($req);
				while( $row = $this->db->db_fetch_array($res))
					{
					if(isAccessValid("faqcat_groups", $row['id']))
						{
						$req = "insert into faqresults select * from faqqr where question ".$this->like." and idcat='".$row['id']."'";
						$this->db->db_query($req);

						$req = "insert into faqresults select * from faqqr where response ".$this->like." and idcat='".$row['id']."'";
						$this->db->db_query($req);
						}
					}

				$req = "select count(*) from faqresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->faqpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->faqnext = $GLOBALS['babUrl']."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat".$pat."&what=".$this->what;
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
			
			if( empty($item) || $item == "e")
				{
				$req = "create temporary table filresults select * from files where 0";
				$this->db->db_query($req);
				$req = "alter table filresults add unique (id)";
				$this->db->db_query($req);
				$aclfm = fileManagerAccessLevel();
				$private = false;
				for( $i = 0; $i < count($aclfm['id']); $i++)
					{
					if( $aclfm['pu'][$i] == 1)
						{
						$req = "insert into filresults select * from files where name ".$this->like." and id_owner='".$aclfm['id'][$i]."' and bgroup='Y' and state='' and confirmed='Y'";
						$this->db->db_query($req);						

						$req = "insert into filresults select * from files where description ".$this->like." and id_owner='".$aclfm['id'][$i]."' and bgroup='Y' and state='' and confirmed='Y'";
						$this->db->db_query($req);						

						$req = "insert into filresults select * from files where keywords ".$this->like." and id_owner='".$aclfm['id'][$i]."' and bgroup='Y' and state='' and confirmed='Y'";
						$this->db->db_query($req);						
						}
					if( $aclfm['pr'][$i] == 1)
						$private = true;
					}

				if( $private)
					{
					$req = "insert into filresults select * from files where name ".$this->like." and id_owner='".$BAB_SESS_USERID."' and bgroup='N' and state='' and confirmed='Y'";
					$this->db->db_query($req);						
					$req = "insert into filresults select * from files where description ".$this->like." and id_owner='".$BAB_SESS_USERID."' and bgroup='N' and state='' and confirmed='Y'";
					$this->db->db_query($req);						
					$req = "insert into filresults select * from files where keywords ".$this->like." and id_owner='".$BAB_SESS_USERID."' and bgroup='N' and state='' and confirmed='Y'";
					$this->db->db_query($req);						
					}

				$req = "select count(*) from filresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->filpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->filnext = $GLOBALS['babUrl']."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat".$pat."&what=".$this->what;
					}
				else
					{
					$this->filpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->filnext = 0;
					}

				$req = "select * from filresults limit ".$pos.", ".$babLimit;
				$this->resfil = $this->db->db_query($req);
				$this->countfil = $this->db->db_num_rows($this->resfil);
				}

			if( (empty($item) || $item == "d") && !empty($BAB_SESS_USERID))
				{
				$req = "select count(*) from notes where content ".$this->like." and id_user='".$BAB_SESS_USERID."'";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->notpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->notnext = $GLOBALS['babUrl']."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat".$pat."&what=".$this->what;
					}
				else
					{
					$this->notpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->notnext = 0;
					}

				$req = "select * from notes where content ".$this->like." and id_user='".$BAB_SESS_USERID."' limit ".$pos.", ".$babLimit;
				$this->resnot = $this->db->db_query($req);
				$this->countnot = $this->db->db_num_rows($this->resnot);
				}

			if( empty($item) || $item == "f")
				{
				$req = "create temporary table conresults select * from contacts where 0";
				$this->db->db_query($req);
				$req = "alter table conresults add unique (id)";
				$this->db->db_query($req);
				$req = "insert into conresults select * from contacts where owner='".$BAB_SESS_USERID."' and firstname ".$this->like." order by lastname, firstname asc";
				$this->db->db_query($req);

				$req = "insert into conresults select * from contacts where owner='".$BAB_SESS_USERID."' and lastname ".$this->like." order by lastname, firstname asc";
				$this->db->db_query($req);

				$req = "insert into conresults select * from contacts where owner='".$BAB_SESS_USERID."' and email ".$this->like." order by lastname, firstname asc";
				$this->db->db_query($req);

				$req = "insert into conresults select * from contacts where owner='".$BAB_SESS_USERID."' and compagny ".$this->like." order by lastname, firstname asc";
				$this->db->db_query($req);

				$req = "insert into conresults select * from contacts where owner='".$BAB_SESS_USERID."' and jobtitle ".$this->like." order by lastname, firstname asc";
				$this->db->db_query($req);

				$req = "insert into conresults select * from contacts where owner='".$BAB_SESS_USERID."' and businessaddress ".$this->like." order by lastname, firstname asc";
				$this->db->db_query($req);

				$req = "insert into conresults select * from contacts where owner='".$BAB_SESS_USERID."' and homeaddress ".$this->like." order by lastname, firstname asc";
				$this->db->db_query($req);

				$req = "select count(*) from conresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->conpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->connext = $GLOBALS['babUrl']."index.php?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat".$pat."&what=".$this->what;
					}
				else
					{
					$this->conpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->connext = 0;
					}

				$req = "select * from conresults limit ".$pos.", ".$babLimit;
				$this->rescon = $this->db->db_query($req);
				$this->countcon = $this->db->db_num_rows($this->rescon);
				}
			}

		function getnextart()
			{
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $this->db->db_fetch_array($this->resart);
				$this->article = $arr['title'];
				$this->articleurl = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=search&idx=a&id=".$arr['id']."&w=".$this->what."')";
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
				$this->com = $arr['subject'];
				$this->comurl = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=search&idx=ac&idt=".$arr['id_topic']."&ida=".$arr['id_article']."&idc=".$arr['id']."&w=".$this->what."')";
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
				$this->post = $arr['subject'];
				$this->posturl = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=search&idx=b&idt=".$arr['id_thread']."&idp=".$arr['id']."&w=".$this->what."')";
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
				$this->question = $arr['question'];
				$this->questionurl = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=search&idx=c&idc=".$arr['idcat']."&idq=".$arr['id']."&w=".$this->what."')";
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

		function getnextfil()
			{
			static $i = 0;
			if( $i < $this->countfil)
				{
				$arr = $this->db->db_fetch_array($this->resfil);
				$this->file = $arr['name'];
				if( !empty($arr['description']))
					$this->filedesc = "( ".$arr['description']." )";
				else
					$this->filedesc = "";
				$this->fileurl = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=search&idx=e&id=".$arr['id']."&w=".$this->what."')";
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists filresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextcon()
			{
			static $i = 0;
			if( $i < $this->countcon)
				{
				$arr = $this->db->db_fetch_array($this->rescon);
				$this->fullname = composeName( $arr['firstname'], $arr['lastname']);
				$this->fullnameurl = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=search&idx=f&id=".$arr['id']."&w=".$this->what."')";;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists conresults";
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
				$this->content = $arr['content'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new temp($pat, $item, $what, $pos);
	$body->babecho(	babPrintTemplate($temp,"search.html", "searchresult"));
	}

function viewArticle($article, $w)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $title;
		var $topic;
		var $babCss;

		function temp($article, $w)
			{
			$this->babCss = babPrintTemplate($this,"config.html", "babCss");
			$db = new db_mysql();
			$req = "select * from articles where id='$article' and confirmed='Y'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
	
			$this->content = highlightWord( $w, locateArticle($arr['body']));
			$this->title = highlightWord( $w, $arr['title']);
			$this->topic =getCategoryTitle($arr['id_topic']);
			}
		}
	
	$temp = new temp($article, $w);
	echo babPrintTemplate($temp,"search.html", "viewart");
	}

function viewComment($topics, $article, $com, $w)
	{
	global $body;
	
	class ctp
		{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();
		var $babCss;

		function ctp($topics, $article, $com, $w)
			{
			$this->babCss = babPrintTemplate($this,"config.html", "babCss");
			$this->babUrl = $GLOBALS['babUrl'];
			$this->sitename = $GLOBALS['babSiteName'];
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
			$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
			$this->arr['subject'] = highlightWord( $w, $this->arr['subject']);
			$this->arr['message'] = highlightWord( $w, $this->arr['message']);
			}
		}

	$ctp = new ctp($topics, $article, $com, $w);
	echo babPrintTemplate($ctp,"search.html", "viewcom");
	}

function viewPost($thread, $post, $w)
	{
	global $body;

	class temp
		{
	
		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $babCss;

		function temp($thread, $post, $w)
			{
			$db = new db_mysql();
			$req = "select forum from threads where id='".$thread."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			$this->title = getForumName($arr['forum']);
			$req = "select * from posts where id='".$post."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			$this->postdate = bab_strftime(bab_mktime($arr['date']));
			$this->postauthor = $arr['author'];
			$this->postsubject = $arr['subject'];
			$this->postmessage = $arr['message'];
			$this->postsubject = highlightWord( $w, $arr['subject']);
			$this->postmessage = highlightWord( $w, $arr['message']);
			$this->babCss = babPrintTemplate($this,"config.html", "babCss");
			}
		}
	
	$temp = new temp($thread, $post, $w);
	echo babPrintTemplate($temp,"search.html", "viewfor");
	}

function viewQuestion($idcat, $id, $w)
	{
	global $body;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $babCss;

		function temp($idcat, $id, $w)
			{
			$this->babCss = babPrintTemplate($this,"config.html", "babCss");
			$this->db = new db_mysql();
			$req = "select * from faqqr where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->arr['question'] = highlightWord( $w, $this->arr['question']);
			$this->arr['response'] = highlightWord( $w, $this->arr['response']);
			$req = "select category from faqcat where id='$idcat'";
			$a = $this->db->db_fetch_array($this->db->db_query($req));
			$this->title = highlightWord( $w,  $a['category']);
			}

		}

	$temp = new temp($idcat, $id, $w);
	echo babPrintTemplate($temp,"search.html", "viewfaq");
	return true;
	}

function viewFile($id, $w)
	{
	global $body;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $babCss;
		var $description;
		var $keywords;
		var $modified;
		var $postedby;
		var $modifiedtxt;
		var $postedbytxt;
		var $sizetxt;
		var $size;
		var $download;
		var $geturl;

		function temp($id, $w)
			{
			$this->description = babTranslate("Description");
			$this->keywords = babTranslate("Keywords");
			$this->modifiedtxt = babTranslate("Modified");
			$this->postedbytxt = babTranslate("Posted by");
			$this->download = babTranslate("Download");
			$this->sizetxt = babTranslate("Size");
			$this->babCss = babPrintTemplate($this,"config.html", "babCss");
			$this->db = new db_mysql();
			$req = "select * from files where id='$id' and state='' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$aclfm = fileManagerAccessLevel();
			$access = false;
			if( $this->arr['bgroup'] == "Y")
				{
				for( $i = 0; $i < count($aclfm['id']); $i++)
					{
					if( $aclfm['id'][$i] == $this->arr['id_owner'] && $aclfm['pu'][$i] == 1)
						{
						$access = true;
						break;
						}
					}
				}
			else if( !empty($GLOBALS['BAB_SESS_USERID']) && $this->arr['id_owner'] == $GLOBALS['BAB_SESS_USERID'])
				{
				if( in_array(1, $aclfm['pr']))
					{
					$access = true;
					}
				}

			if( $access )
				{
				include $GLOBALS['babInstallPath']."utilit/fileincl.php";
				$this->title = $this->arr['name'];
				$this->arr['description'] = highlightWord( $w, $this->arr['description']);
				$this->arr['keywords'] = highlightWord( $w, $this->arr['keywords']);
				$this->modified = date("d/m/Y H:i", bab_mktime($this->arr['modified']));
				$this->postedby = getUserName($this->arr['author']);
				$this->geturl = $GLOBALS['babUrl']."index.php?tg=fileman&idx=get&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".$this->arr['path']."&file=".$this->arr['name'];
				if( $this->arr['bgroup'] == "Y")
					$fstat = stat($GLOBALS['babUploadPath']."/G".$this->arr['id_owner']."/".$this->arr['path']."/".$this->arr['name']);
				else
					$fstat = stat($GLOBALS['babUploadPath']."/U".$this->arr['id_owner']."/".$this->arr['path']."/".$this->arr['name']);
				$this->size = formatSize($fstat[7])." ".babTranslate("Kb");
				}
			else
				{
				$this->title = babTranslate("Access denied");
				$this->arr['description'] = "";
				$this->arr['keywords'] = "";
				$this->modified = "";
				$this->postedby = "";
				$this->geturl = "";
				}
			}

		}

	$temp = new temp($id, $w);
	echo babPrintTemplate($temp,"search.html", "viewfil");
	return true;
	}


function viewContact($id, $what)
	{
	class temp
		{
		var $firstname;
		var $lastname;
		var $email;
		var $compagny;
		var $hometel;
		var $mobiletel;
		var $businesstel;
		var $businessfax;
		var $jobtitle;
		var $businessaddress;
		var $homeaddress;
		var $firstnameval;
		var $lastnameval;
		var $emailval;
		var $compagnyval;
		var $hometelval;
		var $mobiletelval;
		var $businesstelval;
		var $businessfaxval;
		var $jobtitleval;
		var $businessaddressval;
		var $homeaddressval;
		var $addcontactval;
		var $cancel;
		var $babCss;
		var $msgerror;

		function temp($id, $what)
			{
			global $BAB_SESS_USERID;
			$this->firstname = babTranslate("First Name");
			$this->lastname = babTranslate("Last Name");
			$this->email = babTranslate("Email");
			$this->compagny = babTranslate("Compagny");
			$this->hometel = babTranslate("Home Tel");
			$this->mobiletel = babTranslate("Mobile Tel");
			$this->businesstel = babTranslate("Business Tel");
			$this->businessfax = babTranslate("Business Fax");
			$this->jobtitle = babTranslate("Job Title");
			$this->businessaddress = babTranslate("Business Address");
			$this->homeaddress = babTranslate("Home Address");
			$this->cancel = babTranslate("Cancel");
			$this->babCss = babPrintTemplate($this,"config.html", "babCss");
			$this->msgerror = "";

			$db = new db_mysql();
			$req = "select * from contacts where id='".$id."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			if( !empty($BAB_SESS_USERID) && $arr['owner'] == $BAB_SESS_USERID )
				{
				$this->firstnameval = $arr['firstname'];
				$this->lastnameval = $arr['lastname'];
				$this->emailval = $arr['email'];
				$this->compagnyval = $arr['compagny'];
				$this->hometelval = $arr['hometel'];
				$this->mobiletelval = $arr['mobiletel'];
				$this->businesstelval = $arr['businesstel'];
				$this->businessfaxval = $arr['businessfax'];
				$this->jobtitleval = $arr['jobtitle'];
				$this->businessaddressval = $arr['businessaddress'];
				$this->homeaddressval = $arr['homeaddress'];
				}
			else
				{
				$this->msgerror = babTranslate("You don't have access to this contact");
				$this->firstnameval = "";
				$this->lastnameval = "";
				$this->emailval = "";
				$this->compagnyval = "";
				$this->hometelval = "";
				$this->mobiletelval = "";
				$this->businesstelval = "";
				$this->businessfaxval = "";
				$this->jobtitleval = "";
				$this->businessaddressval = "";
				$this->homeaddressval = "";
				}
			}
		}

	$temp = new temp($id, $what);
	echo babPrintTemplate($temp,"search.html", "viewcon");
	}

if( !isset($pos))
	$pos = 0;

if( !isset($what))
	$what = "";

if( !isset($idx))
	$idx = "";

switch($idx)
	{
	case "a":
		viewArticle($id, $w);
		exit;
		break;

	case "ac":
		viewComment($idt, $ida, $idc, $w);
		exit;
		break;

	case "b":
		viewPost($idt, $idp, $w);
		exit;
		break;

	case "c":
		viewQuestion($idc, $idq, $w);
		exit;
		break;

	case "e":
		viewFile($id, $w);
		exit;
		break;

	case "f":
		viewContact($id, $w);
		exit;
		break;

	case "find":
		$body->title = babTranslate("Search");
		searchKeyword($pat, $what);
		startSearch($pat, $item, $what, $pos);
		break;

	default:
		$body->title = babTranslate("Search");
		searchKeyword($pat, $what);
		break;
	}

$body->setCurrentItemMenu($idx);
?>

