<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";

function listCategories($cat)
	{
	global $body;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $namecategory;
		var $articles;
		var $urlarticles;
		var $nbarticles;
		var $idcat;
		var $arrid = array();
		var $waiting;
		var $newa;
		var $newc;

		function temp($cat)
			{
			global $body, $BAB_SESS_USERID;
			$this->articles = babTranslate("Article") ."(s)";
			$this->waiting = babTranslate("Waiting");
			$this->db = new db_mysql();
			$req = "select topics.* from topics join topics_categories where topics.id_cat=topics_categories.id and  topics.id_cat='".$cat."'";
			$res = $this->db->db_query($req);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(isAccessValid("topicsview_groups", $row['id']) )
					{
					array_push($this->arrid, $row['id']);
					}
				}
			$this->idcat = $cat;
			$this->count = count($this->arrid);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{			
				$this->arr = $this->db->db_fetch_array($this->db->db_query("select * from topics where id='".$this->arrid[$i]."'"));
				$this->arr['description'] = $this->arr['description'];
				$this->namecategory = $this->arr['category'];
				$req = "select count(*) as total from articles where id_topic='".$this->arr['id']."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];

				$req = "select * from articles where id_topic='".$this->arr['id']."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$this->newa = $this->db->db_num_rows($res);

				$req = "select * from comments where id_topic='".$this->arr['id']."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$this->newc = $this->db->db_num_rows($res);
				$this->urlarticles = $GLOBALS['babUrl']."index.php?tg=articles&topics=".$this->arr['id']."&new=".$this->newa."&newc=".$this->newc;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($cat);
	$body->babecho(	babPrintTemplate($temp,"topusr.html", "categorylist"));
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
		$catname = getTopicCategoryTitle($cat);
		$body->title = babTranslate("List of all topics"). " [ " . $catname . " ]";
		if( listCategories($cat) > 0 )
			{
			$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topics&idx=list&cat=".$cat);
			}
		else
			$body->title = babTranslate("There is no topic"). " [ " . $catname . " ]";
		break;
	}
$body->setCurrentItemMenu($idx);

?>