<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
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
			$req = "select ".BAB_TOPICS_TBL.".* from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id and  ".BAB_TOPICS_TBL.".id_cat='".$cat."' order by ordering asc";
			$res = $this->db->db_query($req);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(in_array($row['id'], $babBody->topview))
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
		$babLevelTwo = bab_getTopicCategoryTitle($cat);
		$babBody->title = bab_translate("List of all topics"). " [ " . $babLevelTwo . " ]";
		if( listCategories($cat) > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
			}
		else
			$babBody->title = bab_translate("There is no topic"). " [ " . $babLevelTwo . " ]";
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
