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

function listTopicCategory($cat)
	{
	global $babBody, $babDB;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $topicscount;
		var $topicname;
		var $topiccategoryname;
		var $topicdescription;
		var $articlestxt;
		var $articlesurl;
		var $articlescount;
		var $idcat;
		var $arrid = array();
		var $arrcatid = array();
		var $arrparents = array();
		var $waitingtxt;
		var $waitingarticlescount;
		var $waitingcommentscount;
		var $submiturl;
		var $submittxt;
		var $childscount;
		var $childname;
		var $childurl;
		var $parentscount;
		var $parentname;
		var $parenturl;

		function temp($cat)
			{
			global $babBody, $BAB_SESS_USERID;
			$this->articlestxt = bab_translate("Article") ."(s)";
			$this->waitingtxt = bab_translate("Waiting");
			$this->submittxt = bab_translate("Submit");
			$this->db = $GLOBALS['babDB'];
			$this->idcat = $cat;
			if( $cat != 0 )
				{
				$req = "select ".BAB_TOPICS_TBL.".* from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id and  ".BAB_TOPICS_TBL.".id_cat='".$cat."' order by ordering asc";
				$res = $this->db->db_query($req);
				while( $row = $this->db->db_fetch_array($res))
					{
					if(in_array($row['id'], $babBody->topview))
						{
						array_push($this->arrid, $row['id']);
						}
					}
				$this->topicscount = count($this->arrid);
				$this->topiccategoryname = bab_getTopicCategoryTitle($cat);
				}
			else
				{
				$this->topicscount = 0;
				$this->topiccategoryname = "";
				}

			for( $i = 0; $i < count($babBody->topview); $i++)
				{
				$res = $this->db->db_query("select tc.id from ".BAB_TOPICS_CATEGORIES_TBL." tc, ".BAB_TOPICS_TBL." t where t.id='".$babBody->topview[$i]."' and t.id_cat=tc.id and tc.id_parent='".$cat."'");
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$arr = $this->db->db_fetch_array($res);
					if(!in_array($arr['id'], $this->arrcatid))
						{
						array_push($this->arrcatid, $arr['id']);
						}
					}
				}
			$this->childscount = count($this->arrcatid);

			if( $cat != 0 )
				{
				$this->arrparents[] = $cat;
				$res = $this->db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'");
				while($arr = $this->db->db_fetch_array($res))
					{
					if( $arr['id_parent'] == 0 )
						break;
					$this->arrparents[] = $arr['id_parent'];
					$res = $this->db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arr['id_parent']."'");
					}

				}
			$this->arrparents[] = 0;

			$this->parentscount = count($this->arrparents);
			$this->arrparents = array_reverse($this->arrparents);
			}

		function getnexttopic()
			{
			static $i = 0;
			if( $i < $this->topicscount)
				{			
				$this->submiturl = "";
				$this->arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_TOPICS_TBL." where id='".$this->arrid[$i]."'"));
				$this->topicname = $this->arr['category'];
				$this->topicdescription = $this->arr['description'];
				$res = $this->db->db_query("select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and confirmed='Y'");
				$arr2 = $this->db->db_fetch_array($res);
				$this->articlescount = $arr2['total'];
				if( $this->articlescount == 0 )
					$this->submiturl = $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$this->arr['id'];

				$res = $this->db->db_query("select * from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and confirmed='N'");
				$this->waitingarticlescount = $this->db->db_num_rows($res);

				$res = $this->db->db_query("select * from ".BAB_COMMENTS_TBL." where id_topic='".$this->arr['id']."' and confirmed='N'");
				$this->waitingcommentscount = $this->db->db_num_rows($res);
				$this->articlesurl = $GLOBALS['babUrlScript']."?tg=articles&topics=".$this->arr['id']."&new=".$this->waitingarticlescount."&newc=".$this->waitingcommentscount;
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextchild()
			{
			static $i = 0;
			if( $i < $this->childscount)
				{
				$this->childname = bab_getTopicCategoryTitle($this->arrcatid[$i]);
				$this->childurl = $GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arrcatid[$i];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextparent()
			{
			static $i = 0;
			if( $i < $this->parentscount)
				{
				if( $this->arrparents[$i] == 0 )
					$this->parentname = bab_translate("Top");
				else
					$this->parentname = bab_getTopicCategoryTitle($this->arrparents[$i]);
				$this->parenturl = $GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arrparents[$i];
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$template = "default";
	if( $cat != 0 )
		{
		$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['display_tmpl'] != '' )
				$template = $arr['display_tmpl'];
			}
		}

	$temp = new temp($cat);
	$babBody->babecho( bab_printTemplate($temp,"topcatdisplay.html", $template));
	return $temp->topicscount;
	}

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if(!isset($cat))
	{
	$cat = 0;
	}

switch($idx)
	{
	default:
	case "list":
		$babLevelTwo = bab_getTopicCategoryTitle($cat);
		$babBody->title = "";
		listTopicCategory($cat);
		break;
	}
?>
