<?php
include $babInstallPath."utilit/topincl.php";

function listArticles()
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
				if(isAccessValid("topicsview_groups", $row[id]))
					{
					$req = "select * from articles where id_topic='".$row[id]."' and confirmed='Y' order by date desc";
					$res2 = $this->db->db_query($req);

					if( $res2 && $this->db->db_num_rows($res2) > 0)
						{
						array_push($this->arrid, $row[id]);
						}
					}
				}
			$this->counttop = count($this->arrid);

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
						$this->commentsurl = $GLOBALS[babUrl]."index.php?tg=comments&idx=List&topics=".$this->arrid[$i]."&article=".$this->arr[id];
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

					$this->moreurl = $GLOBALS[babUrl]."index.php?tg=articles&idx=More&topics=".$this->arrid[$i]."&article=".$this->arr[id];
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

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

switch($idx)
	{

	default:
	case "list":
		$body->title = babTranslate("List of articles");
		listArticles();
		break;
	}
$body->setCurrentItemMenu($idx);

?>