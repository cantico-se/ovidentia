<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/topincl.php";

function listCategories($cat)
	{
	global $babBody;
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
		var $urlsubmit;
		var $txtsubmit;

		function temp($cat)
			{
			global $babBody, $BAB_SESS_USERID;
			$this->articles = bab_translate("Article") ."(s)";
			$this->waiting = bab_translate("Waiting");
			$this->txtsubmit = bab_translate("Submit");
			$this->db = $GLOBALS['babDB'];
			$req = "select ".BAB_TOPICS_TBL.".* from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id and  ".BAB_TOPICS_TBL.".id_cat='".$cat."'";
			$res = $this->db->db_query($req);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $row['id']) )
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
				$this->urlsubmit = "";
				$this->arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_TOPICS_TBL." where id='".$this->arrid[$i]."'"));
				$this->arr['description'] = $this->arr['description'];
				$this->namecategory = $this->arr['category'];
				$req = "select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				if( $this->nbarticles == 0 )
					$this->urlsubmit = $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$this->arr['id'];

				$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$this->newa = $this->db->db_num_rows($res);

				$req = "select * from ".BAB_COMMENTS_TBL." where id_topic='".$this->arr['id']."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$this->newc = $this->db->db_num_rows($res);
				$this->urlarticles = $GLOBALS['babUrlScript']."?tg=articles&topics=".$this->arr['id']."&new=".$this->newa."&newc=".$this->newc;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($cat);
	$babBody->babecho(	bab_printTemplate($temp,"topusr.html", "categorylist"));
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
		$catname = bab_getTopicCategoryTitle($cat);
		$babBody->title = bab_translate("List of all topics"). " [ " . $catname . " ]";
		if( listCategories($cat) > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
			}
		else
			$babBody->title = bab_translate("There is no topic"). " [ " . $catname . " ]";
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>