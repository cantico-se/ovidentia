<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";

function oldListArticles()
	{
	global $body;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $arrid = array();
		var $db;
		var $count;
		var $counttop;
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

		function temp()
			{
			$this->db = new db_mysql();
			$req = "select * from topics";
			$res = $this->db->db_query($req);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(isAccessValid("topicsview_groups", $row['id']))
					{
					$req = "select * from articles where id_topic='".$row['id']."' and confirmed='Y' order by date desc";
					$res2 = $this->db->db_query($req);

					if( $res2 && $this->db->db_num_rows($res2) > 0)
						{
						array_push($this->arrid, $row['id']);
						}
					}
				}
			$this->counttop = count($this->arrid);
			//echo $this->counttop;

			$this->com = false;
			$this->morename = babTranslate("Read More");
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->counttop)
				{
				$req = "select * from articles where id_topic='".$this->arrid[$i]."' and confirmed='Y' order by date desc";
				$this->res = $this->db->db_query($req);

				if( $this->res && $this->db->db_num_rows($this->res) > 0)
					{
					$this->arr = $this->db->db_fetch_array($this->res);
					$this->author = babTranslate("by") . " ". getArticleAuthor($this->arr['id']). " - ". getArticleDate($this->arr['id']);
					$this->content = $this->arr['head'];

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
						$this->commentsurl = $GLOBALS['babUrl']."index.php?tg=comments&idx=List&topics=".$this->arrid[$i]."&article=".$this->arr['id'];
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

					$this->moreurl = $GLOBALS['babUrl']."index.php?tg=articles&idx=More&topics=".$this->arrid[$i]."&article=".$this->arr['id'];
					if( isset($new) && $new > 0)
						$this->moreurl .= "&new=".$new;

					$this->moreurl .= "&newc=".$this->newc;
					$this->morename = babTranslate("Read more")."...";
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"articles.html", "introlist"));
	return $temp->count;
	}

function ListArticles($idgroup)
	{
	global $body;

	class temp
		{	
		var $title;
		var $content;
		var $db;
		var $countres;
		var $res;
		var $author;
		var $moreurl;
		var $morename;

		function temp($idgroup)
			{
			$this->db = new db_mysql();
			$req = "select * from sites where name='".$GLOBALS['babSiteName']."'";
			$res = $this->db->db_query($req);
			if( !$res || $this->db->db_num_rows($res) < 1)
				{
				$req = "insert into sites ( name, adminemail, lang ) values ('" .$GLOBALS['babSiteName']. "', '" . $GLOBALS['babAdminEmail']. "', '" . $GLOBALS['babLanguage']. "')";
				$res = $this->db->db_query($req);
				$idsite = $this->db->db_insert_id();
				}
			else
				{
				$arr = $this->db->db_fetch_array($res);
				$idsite = $arr['id'];
				}
			$req = "select * from homepages where id_group='".$idgroup."' and id_site='".$idsite."' and ordering!='0' order by ordering asc";
			$this->res = $this->db->db_query($req);
			$this->countres = $this->db->db_num_rows($this->res);
			$this->morename = babTranslate("Read More");
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->countres)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from articles where id='".$arr['id_article']."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->title = $arr['title'];
				$this->content = $arr['head'];
				$this->author = babTranslate("by") . " ". getArticleAuthor($arr['id']). " - ". getArticleDate($arr['id']);
				$this->moreurl = $GLOBALS['babUrl']."index.php?tg=entry&idx=more&article=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($idgroup);
	$body->babecho(	babPrintTemplate($temp,"entry.html", "homepage0"));
	}

function readMore($article)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $db;
		var $count;
		var $res;
		var $title;

		function temp($article)
			{
			$this->db = new db_mysql();
			$req = "select * from articles where id='$article' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->content = $arr['body'];
				$this->title = $arr['title'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($article);
	$body->babecho(	babPrintTemplate($temp,"entry.html", "readmore"));
	}

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if(!isset($idg))
	{
	$idg = 2; // non registered users
	}

if( $LOGGED_IN)
	$idg = 1; // registered users

switch($idx)
	{
	case "more":
		readMore($article);
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=entry");
		$body->addItemMenu("more", babTranslate("Article"), $GLOBALS['babUrl']."index.php?tg=entry&idx=more");
		break;

	default:
	case "list":
		$body->title = babTranslate("List of articles");
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS['babUrl']."index.php?tg=entry");
		listArticles($idg);
		break;
	}
$body->setCurrentItemMenu($idx);

?>