<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";

function oldListArticles()
	{
	global $babBody;

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
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_TOPICS_TBL."";
			$res = $this->db->db_query($req);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $row['id']))
					{
					$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$row['id']."' and confirmed='Y' order by date desc";
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
			$this->morename = bab_translate("Read More");
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->counttop)
				{
				$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$this->arrid[$i]."' and confirmed='Y' order by date desc";
				$this->res = $this->db->db_query($req);

				if( $this->res && $this->db->db_num_rows($this->res) > 0)
					{
					$this->arr = $this->db->db_fetch_array($this->res);
					$this->author = bab_translate("by") . " ". bab_getArticleAuthor($this->arr['id']). " - ". bab_getArticleDate($this->arr['id']);
					$this->content = $this->arr['head'];

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
						$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->arrid[$i]."&article=".$this->arr['id'];
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

					$this->moreurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->arrid[$i]."&article=".$this->arr['id'];
					if( isset($new) && $new > 0)
						$this->moreurl .= "&new=".$new;

					$this->moreurl .= "&newc=".$this->newc;
					$this->morename = bab_translate("Read more")."...";
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "introlist"));
	return $temp->count;
	}

function ListArticles($idgroup)
	{
	global $babBody;

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
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
			$res = $this->db->db_query($req);
			if( !$res || $this->db->db_num_rows($res) < 1)
				{
				$req = "insert into ".BAB_SITES_TBL." ( name, adminemail, lang ) values ('" .addslashes($GLOBALS['babSiteName']). "', '" . $GLOBALS['babAdminEmail']. "', '" . $GLOBALS['babLanguage']. "')";
				$res = $this->db->db_query($req);
				$idsite = $this->db->db_insert_id();
				}
			else
				{
				$arr = $this->db->db_fetch_array($res);
				$idsite = $arr['id'];
				}
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='".$idgroup."' and id_site='".$idsite."' and ordering!='0' order by ordering asc";
			$this->res = $this->db->db_query($req);
			$this->countres = $this->db->db_num_rows($this->res);
			$this->morename = bab_translate("Read More");
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->countres)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$arr['id_article']."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->title = $arr['title'];
				$this->content = bab_replace($arr['head']);
				$this->author = bab_translate("by") . " ". bab_getArticleAuthor($arr['id']). " - ". bab_getArticleDate($arr['id']);
				$this->moreurl = $GLOBALS['babUrlScript']."?tg=entry&idx=more&article=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($idgroup);
	$babBody->babecho(	bab_printTemplate($temp,"entry.html", "homepage0"));
	}

function readMore($article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $db;
		var $count;
		var $res;
		var $title;
		var $author;

		function temp($article)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->content = bab_replace($arr['body']);
				$this->title = $arr['title'];
				$this->author = bab_translate("by") . " ". bab_getArticleAuthor($arr['id']). " - ". bab_getArticleDate($arr['id']);
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($article);
	$babBody->babecho(	bab_printTemplate($temp,"entry.html", "readmore"));
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

if( $BAB_SESS_LOGGED)
	$idg = 1; // registered users

switch($idx)
	{
	case "more":
		readMore($article);
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=entry");
		$babBody->addItemMenu("more", bab_translate("Article"), $GLOBALS['babUrlScript']."?tg=entry&idx=more");
		break;

	default:
	case "list":
		$babBody->title = bab_translate("List of articles");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=entry");
		listArticles($idg);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>