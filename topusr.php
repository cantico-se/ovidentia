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
include_once 'base.php';
include_once $babInstallPath.'utilit/topincl.php';

function listTopicCategory($cat)
	{
	global $babBody, $babDB;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
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
		var $istopcat;
		var $burl;

		function temp($cat)
			{
			global $babBody, $babDB, $BAB_SESS_USERID;
			$this->articlestxt = bab_translate("Article") ."(s)";
			$this->waitingtxt = bab_translate("Waiting");
			$this->submittxt = bab_translate("Submit");
			$this->idcat = $cat; /* don't change variable name */

			$arrtopcat = array();
			$arrtop = array();
			$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$babDB->db_escape_string($cat)."' order by ordering asc";
			$res = $babDB->db_query($req);
			$topcatview = $babBody->get_topcatview();
			while( $row = $babDB->db_fetch_array($res))
				{
				if($row['type'] == '2' && isset($babBody->topview[$row['id_topcat']]))
					{
					array_push($this->arrid, array($row['id_topcat'], 2));
					array_push($arrtop, $row['id_topcat']);
					}
				else if( $row['type'] == '1' && isset($topcatview[$row['id_topcat']]))
					{
					array_push($this->arrid, array($row['id_topcat'], 1));
					array_push($arrtopcat, $row['id_topcat']);
					}
				}
			$this->count = count($this->arrid);

			if( $cat != 0 )
				{
				$this->arrparents[] = $cat;
				$topcats = $babBody->get_topcats();
				while( $topcats[$cat]['parent'] != 0 )
					{
					$this->arrparents[] = $topcats[$cat]['parent'];
					$cat = $topcats[$cat]['parent'];
					}
				}
			$this->arrparents[] = 0;

			$this->parentscount = count($this->arrparents);
			$this->arrparents = array_reverse($this->arrparents);

			if( count($arrtop) > 0 )
				{
				$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id IN (".$babDB->quote($arrtop).")");
				while( $arr = $babDB->db_fetch_array($res))
					{
					for($i=0; $i < $this->count; $i++)
						{
						if( $this->arrid[$i][1] == 2 && $this->arrid[$i][0]== $arr['id'])
							{
							$this->arrid[$i]['title'] = $arr['category'];
							$this->arrid[$i]['description'] = bab_replace($arr['description']);
							$this->arrid[$i]['confirmed'] = 0;
							}
						}
					}

				$res = $babDB->db_query("select count(id) total, id_topic from ".BAB_ARTICLES_TBL." where id IN (".$babDB->quote($arrtop).") GROUP by id_topic");
				while( $arr = $babDB->db_fetch_array($res))
					{
					for($i=0; $i < $this->count; $i++)
						{
						if( $this->arrid[$i][1] == 2 && $this->arrid[$i][0]== $arr['id_topic'])
							{
							$this->arrid[$i]['confirmed'] = $arr['total'];
							}
						}
					}
				}	

			if( count($arrtopcat) > 0 )
				{
				$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN (".$babDB->quote($arrtopcat).")");
				while( $arr = $babDB->db_fetch_array($res))
					{
					for($i=0; $i < $this->count; $i++)
						{
						if( $this->arrid[$i][1] == 1 && $this->arrid[$i][0]== $arr['id'])
							{
							$this->arrid[$i]['title'] = $arr['title'];
							$this->arrid[$i]['description'] = $arr['description'];
							}
						}
					}
				}


			}

		function getnext()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->submiturl = "";
				$this->childurl = "";
				$this->childname = $this->arrid[$i]['title'];
				$this->childdescription = trim($this->arrid[$i]['description']);
				if( $this->arrid[$i][1] == 1 )
					{
					$this->childurl = $GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arrid[$i][0];
					$this->istopcat = true;
					$this->idtopiccategory = $this->arrid[$i][0]; /* don't change variable name */
					$this->idtopic = ''; /* don't change variable name */
					}
				else
					{
					$this->idtopiccategory = '';
					$this->idtopic = $this->arrid[$i][0];
					$this->istopcat = false;
					if( $this->arrid[$i]['confirmed'] == 0 )
						$this->submiturl = $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$this->arrid[$i][0];
					$this->waitingarticlescount = 0;
					$this->waitingcommentscount = 0;
					$this->articlesurl = $GLOBALS['babUrlScript']."?tg=articles&topics=".$this->arrid[$i][0]."&new=".$this->waitingarticlescount."&newc=".$this->waitingcommentscount;
					$this->childurl = $this->articlesurl;
					}

				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextparent()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->parentscount)
				{
				if( $this->arrparents[$i] == 0 ) {
					$this->parentname = bab_translate("Top");
				}
				else {
					$topcats = $babBody->get_topcats();
					$this->parentname = $topcats[$this->arrparents[$i]]['title'];
				}
				$this->parenturl = $GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arrparents[$i];
				if( $i == $this->parentscount - 1 )
					$this->burl = false;
				else
					$this->burl = true;
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
		$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($cat)."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['display_tmpl'] != '' )
				$template = $arr['display_tmpl'];
			}
		}

	$temp = new temp($cat);
	$html = bab_printTemplate($temp,"topcatdisplay.html", $template);
	if (empty($html))
		$html = bab_printTemplate($temp,"topcatdisplay.html", 'default');
	$babBody->babecho( $html );
	return isset($temp->topicscount) ? $temp->topicscount : '';
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
