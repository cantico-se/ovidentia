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
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath."admin/acl.php";

define("BAB_STAT_BCT_TOPIC",		1);
define("BAB_STAT_BCT_ARTICLE",		2);
define("BAB_STAT_BCT_FOLDER",		3);
define("BAB_STAT_BCT_FILE",			4);
define("BAB_STAT_BCT_FORUM",		5);
define("BAB_STAT_BCT_POST",			6);
define("BAB_STAT_BCT_FAQ",			7);
define("BAB_STAT_BCT_QUESTION",		8);


function statBaskets($baskname, $baskdesc)
{
	global $babBody;
	
	class statBasketsCls
		{
		var $updatetxt;

		function statBasketsCls($baskname, $baskdesc)
			{
			global $babBody, $babDB;
			$this->t_rows_per_page = bab_translate("Rows/page");
			$this->t_baskets = bab_translate("Statistics baskets");
			$this->t_addbasket = bab_translate("Add a new basket");
			$this->t_basketname = bab_translate("Name");
			$this->t_basketdesc = bab_translate("Description");
			$this->t_rights = bab_translate("Rights");
			$this->t_edit = bab_translate("Edit");
			$this->t_delete = bab_translate("Delete");
			$this->t_content = bab_translate("Content");

			$this->addtxt = bab_translate("Add");

			$this->addbaskurl = $GLOBALS['babUrlScript']."?tg=statconf&idx=basknew";

			$this->rows_per_page = isset($_POST['rows_per_page']) ? $_POST['rows_per_page'] : 10;
			$this->pos = isset($_POST['pos']) ? $_POST['pos'] : 0;

			list($this->max) = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(*) FROM ".BAB_STATS_BASKETS_TBL." where id_dgowner='".$babBody->currentAdmGroup."'"));

			
			$this->baskdescval = $baskdesc;
			$this->basknameval = $baskname;
			$this->altbg = true;

			if( $this->max > $this->rows_per_page )
				{
				$this->res = $babDB->db_query("SELECT t.* FROM ".BAB_STATS_BASKETS_TBL." t where t.id_dgowner='".$babBody->currentAdmGroup."' order by t.basket_name LIMIT ".$this->pos.",".$this->rows_per_page);
				$this->bmpages =true;
				}
			else
				{
				$this->res = $babDB->db_query("SELECT t.* FROM ".BAB_STATS_BASKETS_TBL." t where t.id_dgowner='".$babBody->currentAdmGroup."' order by basket_name");
				$this->bmpages =false;
				}
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i=0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);

				$this->basketname = $arr['basket_name'];
				$this->baskaccessurl = $GLOBALS['babUrlScript']."?tg=statconf&idx=baskrights&baskid=".$arr['id'];
				$this->baskediturl = $GLOBALS['babUrlScript']."?tg=statconf&idx=baskedit&baskid=".$arr['id'];
				$this->baskdeleteurl = $GLOBALS['babUrlScript']."?tg=statconf&idx=baskdel&baskid=".$arr['id'];
				$this->baskcontenturl = $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcontent&baskid=".$arr['id'];
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}
		}

	$temp = new statBasketsCls($baskname, $baskdesc);
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "baskets"));
}

function statModifyBasket()
{
	global $babBody;
	
	class statModifyBasketsCls
		{
		var $updatetxt;

		function statModifyBasketsCls()
			{
			global $babBody, $babDB;

			$this->t_basketname = bab_translate("Name");
			$this->t_basketdesc = bab_translate("Description");

			$this->updatetxt = bab_translate("Update");

			$this->baskid = $_GET['baskid'];
			$arr = $babDB->db_fetch_array($babDB->db_query("select basket_name, basket_desc from ".BAB_STATS_BASKETS_TBL." where id='".$this->baskid."'"));

			$this->basknameval = $arr['basket_name'];
			$this->baskdescval = $arr['basket_desc'];
			}

		}

	$temp = new statModifyBasketsCls();
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "basket_edit"));
}



function statUpdateContentBasket()
{
	global $babBody;
	
	class statUpdateContentBasketCls
		{
		var $updatetxt;

		function statUpdateContentBasketCls()
			{
			global $babBody, $babDB;

			$this->t_desc_txt = bab_translate("Description");
			$this->t_name_txt = bab_translate("Name");

			$this->t_update_txt = bab_translate("Update");

			$this->baskid = $_GET['baskid'];
			$this->itemid = $_GET['itemid'];
			$arr = $babDB->db_fetch_array($babDB->db_query("select bc_description, bc_type, bc_id from ".BAB_STATS_BASKET_CONTENT_TBL." where id='".$_GET['itemid']."'"));
			$this->ibcdescriptionval = $arr['bc_description'];
			switch($arr['bc_type'])
				{
				case BAB_STAT_BCT_TOPIC:
					$req = "select tt.category as bc_item_name from ".BAB_TOPICS_TBL." tt where tt.id='".$arr['bc_id']."'";
					break;
				case BAB_STAT_BCT_ARTICLE:
					$req = "select at.title as bc_item_name from ".BAB_ARTICLES_TBL." at where at.id='".$arr['bc_id']."'";
					break;
				case BAB_STAT_BCT_FOLDER:
					$req = "select fft.folder as bc_item_name from ".BAB_FM_FOLDERS_TBL." fft where fft.id='".$arr['bc_id']."'";
					break;
				case BAB_STAT_BCT_FILE:
					$req = "select ft.name as bc_item_name from ".BAB_FILES_TBL." ft where ft.id='".$arr['bc_id']."'";
					break;
				case BAB_STAT_BCT_FORUM:
					$req = "select ft.name as bc_item_name from ".BAB_FORUMS_TBL." ft where ft.id='".$arr['bc_id']."'";
					break;
				case BAB_STAT_BCT_POST:
					$req = "select pt.subject as bc_item_name from ".BAB_POSTS_TBL." pt where pt.id='".$arr['bc_id']."'";
					break;
				case BAB_STAT_BCT_FAQ:
					$req = "select ft.category as bc_item_name from ".BAB_FAQCAT_TBL." ft where ft.id='".$arr['bc_id']."'";
					break;
				case BAB_STAT_BCT_QUESTION:
					$req = "select ft.question as bc_item_name from ".BAB_FAQQR_TBL." ft where ft.id='".$arr['bc_id']."'";
					break;
				default:
					$req = '';
					break;
				}

			if( $req)
				{
				$arr = $babDB->db_fetch_array($babDB->db_query($req));
				$this->ibcname = $arr['bc_item_name'];
				}
			else
				{
				$this->ibcname = '';
				}
			}

		}

	$temp = new statUpdateContentBasketCls();
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "basket_content_edit"));
}

function statBrowseBasketItem()
{
	global $babBody;
	
	class statBrowseBasketItemCls
		{
		var $updatetxt;

		function statBrowseBasketItemCls()
			{
			global $babBody, $babDB, $babBodyPopup;

			$this->baskid = $_GET['baskid'];
			$this->what = $_GET['w'];
			require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';
			switch($this->what)
				{
				case 'top':
					$babBodyPopup->title = bab_translate("Articles topic choice");
					$treeView = new bab_ArticleTreeView('article');
					$treeView->setAttributes( BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS | BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS);
					break;
				case 'art':
					$babBodyPopup->title = bab_translate("Article choice");
					$treeView = new bab_ArticleTreeView('article');
					$treeView->setAttributes(BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES | BAB_ARTICLE_TREE_VIEW_SELECTABLE_ARTICLES);
					break;
				case 'fold':
					$babBodyPopup->title = bab_translate("Folder choice");
					break;
				case 'file':
					$babBodyPopup->title = bab_translate("File choice");
					$this->t_name = bab_translate("Files");
					break;
				case 'for':
					$babBodyPopup->title = bab_translate("Forum choice");
					$treeView = new bab_ForumTreeView('forum');
					//$treeView->setAttributes(BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES);
					break;
				case 'post':
					$babBodyPopup->title = bab_translate("Post choice");
					$this->t_name = bab_translate("Posts");
					break;
				case 'faq':
					$babBodyPopup->title = bab_translate("Faq choice");
					$treeView = new bab_FaqTreeView('faq');
					$treeView->setAttributes(BAB_FAQ_TREE_VIEW_SHOW_CATEGORIES | BAB_FAQ_TREE_VIEW_SELECTABLE_CATEGORIES);
					$this->t_name = bab_translate("Faqs");
					break;
				case 'faqqr':
					$babBodyPopup->title = bab_translate("Question choice");
					$this->t_name = bab_translate("Questions");
					break;
				}

			if( isset($treeView))
				{
				$treeView->sort();
				$babBodyPopup->babecho($treeView->printTemplate());
				}
			}

		}

	$temp = new statBrowseBasketItemCls();
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "basket_edit"));
}


function statAddContentBasket()
{
	global $babBody;
	
	class statAddContentBasketCls
	{
		var $updatetxt;
		var $t_what;
		var $t_dialog_attributes;

		function statAddContentBasketCls()
		{
			global $babBody, $babDB;

			$this->t_description = bab_translate("Description");

			$this->t_add = bab_translate("Add");

			$this->baskid = $_GET['baskid'];
			$this->what = $_GET['w'];
			switch($this->what)
				{
				case 'top':
					$this->t_what = 'article';
					$this->t_name = bab_translate("Articles topics");
					$this->t_dialog_attributes = 'show_topics=1&selectable_topics=1';
					break;
				case 'art':
					$this->t_what = 'article';
					$this->t_name = bab_translate("Articles");
					$this->t_dialog_attributes = 'show_articles=1&selectable_articles=1';
					break;
				case 'fold':
					$this->t_what = 'file';
					$this->t_name = bab_translate("Folders");
					$this->t_dialog_attributes = 'show_collective_directories=1&selectable_collective_directories=1';
					break;
				case 'file':
					$this->t_what = 'file';
					$this->t_name = bab_translate("Files");
					$this->t_dialog_attributes = 'show_files=1&selectable_files=1';
					break;
				case 'for':
					$this->t_what = 'forum';
					$this->t_name = bab_translate("Forums");
					$this->t_dialog_attributes = 'show_forums=1&selectable_forums=1';
					break;
				case 'post':
					$this->t_what = 'forum';
					$this->t_name = bab_translate("Posts");
					$this->t_dialog_attributes = 'show_posts=1&selectable_posts=1';
					break;
				case 'faq':
					$this->t_what = 'faq';
					$this->t_name = bab_translate("Faqs");
					$this->t_dialog_attributes = 'show_categories=1&selectable_categories=1';
					break;
				case 'faqqr':
					$this->t_what = 'faq';
					$this->t_name = bab_translate("Questions");
					$this->t_dialog_attributes = 'show_questions=1&selectable_questions=1';
					break;
				default:
					$this->t_dialog_attributes = '';
					break;
				}

			$this->addurl = $GLOBALS['babUrlScript']."?tg=statconf&idx=bcbrowse&w=".$this->what."&baskid=".$this->baskid;
			$this->ibcnameval = '';
			$this->ibcdescriptionval = '';
		}
			
	}

	$temp = new statAddContentBasketCls();
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "basket_content_add"));
}

function statContentBasket($baskid)
{
	global $babBody;
	
	class statContentBasketCls
		{
		var $updatetxt;

		function statContentBasketCls($baskid)
			{
			global $babBody, $babDB;

			$this->t_delete = bab_translate("Delete");
			$this->t_add = bab_translate("Add");

			$this->baskid = $baskid;

			$this->families = array(
				BAB_STAT_BCT_TOPIC => array("Articles topics", "top"),
				BAB_STAT_BCT_ARTICLE => array("Articles", "art"),
				BAB_STAT_BCT_FOLDER => array("Folders", "fold"),
				BAB_STAT_BCT_FILE => array("Files", "file"),
				BAB_STAT_BCT_FORUM=> array("Forums", "for"),
				BAB_STAT_BCT_POST => array("Posts", "post"),
				BAB_STAT_BCT_FAQ => array("Faqs", "faq"),
				BAB_STAT_BCT_QUESTION => array("Questions", "faqqr")
				);

			$this->countf = count($this->families)+1;
			}

		function getnextfamily()
			{
			global $babDB;
			static $k=1;
			if( $k < $this->countf)
				{
				$this->t_familyname = bab_translate($this->families[$k][0]);
				$this->addurl = $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcadd&w=".$this->families[$k][1]."&baskid=".$this->baskid;
				$this->counti = 0;
				switch($k)
					{
					case BAB_STAT_BCT_TOPIC:
						$req = "select sbct.*, tt.category as bc_item_name from ".BAB_STATS_BASKET_CONTENT_TBL." sbct left join ".BAB_TOPICS_TBL." tt on tt.id=sbct.bc_id where sbct.bc_type='".$k."'";
						break;
					case BAB_STAT_BCT_ARTICLE:
						$req = "select sbct.*, at.title as bc_item_name from ".BAB_STATS_BASKET_CONTENT_TBL." sbct left join ".BAB_ARTICLES_TBL." at on at.id=sbct.bc_id where sbct.bc_type='".$k."'";
						break;
					case BAB_STAT_BCT_FOLDER:
						$req = "select sbct.*, fft.folder as bc_item_name from ".BAB_STATS_BASKET_CONTENT_TBL." sbct left join ".BAB_FM_FOLDERS_TBL." fft on fft.id=sbct.bc_id where sbct.bc_type='".$k."'";
						break;
					case BAB_STAT_BCT_FILE:
						$req = "select sbct.*, ft.name as bc_item_name from ".BAB_STATS_BASKET_CONTENT_TBL." sbct left join ".BAB_FILES_TBL." ft on ft.id=sbct.bc_id where sbct.bc_type='".$k."'";
						break;
					case BAB_STAT_BCT_FORUM:
						$req = "select sbct.*, ft.name as bc_item_name from ".BAB_STATS_BASKET_CONTENT_TBL." sbct left join ".BAB_FORUMS_TBL." ft on ft.id=sbct.bc_id where sbct.bc_type='".$k."'";
						break;
					case BAB_STAT_BCT_POST:
						$req = "select sbct.*, pt.subject as bc_item_name from ".BAB_STATS_BASKET_CONTENT_TBL." sbct left join ".BAB_POSTS_TBL." pt on pt.id=sbct.bc_id where sbct.bc_type='".$k."'";
						break;
					case BAB_STAT_BCT_FAQ:
						$req = "select sbct.*, ft.category as bc_item_name from ".BAB_STATS_BASKET_CONTENT_TBL." sbct left join ".BAB_FAQCAT_TBL." ft on ft.id=sbct.bc_id where sbct.bc_type='".$k."'";
						break;
					case BAB_STAT_BCT_QUESTION:
						$req = "select sbct.*, ft.question as bc_item_name from ".BAB_STATS_BASKET_CONTENT_TBL." sbct left join ".BAB_FAQQR_TBL." ft on ft.id=sbct.bc_id where sbct.bc_type='".$k."'";
						break;
					default:
						$req = '';
						break;
					}
				$this->counti = 0;

				if( !empty($req))
					{
					$req .= " and sbct.basket_id = '".$this->baskid."'";
					$this->res = $babDB->db_query($req);
					$this->counti = $babDB->db_num_rows($this->res);
					}
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				$this->counti = 0;
				return false;
				}
			}

		function getnextitem()
			{
			global $babDB;
			static $d=0;
			if( $d < $this->counti)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->itemname = $arr['bc_item_name'];
				$this->itemdesc = $arr['bc_description'];
				$this->deleteurl= $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcontent&action=bcdel&itemid=".$arr['id']."&baskid=".$this->baskid;
				$this->urlbciedit= $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcedit&itemid=".$arr['id']."&baskid=".$this->baskid;

				$d++;
				return true;
				}
			else
				{
				$d = 0;
				return false;
				}
			}

		}

	$temp = new statContentBasketCls($baskid);
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "basket_content"));
}

function statDeleteBasket($id)
	{
	global $babBody;
	
	class statDeleteBasketCls
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function statDeleteBasketCls($id)
			{
			global $babDB;
			$this->message = bab_translate("Are you sure you want to delete this basket");
			list($this->title) = $babDB->db_fetch_row($babDB->db_query("select basket_name from ".BAB_STATS_BASKETS_TBL." where id='".$id."'"));
			$this->warning = bab_translate("WARNING: This operation will delete the basket and all associated datas"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=statconf&idx=bask&baskid=".$id."&action=dbask";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=statconf&idx=bask";
			$this->no = bab_translate("No");
			}
		}

	$temp = new statDeleteBasketCls($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function statPages($url, $page)
{
	global $babBody;
	
	class temp
		{

		var $altbg = true;

		function temp($url, $page)
			{
			global $babBody, $babDB;

			$this->pagetxt = bab_translate("Page");
			$this->urltxt = bab_translate("Url");
			$this->updatetxt = bab_translate("Update");
			$this->deletetxt = bab_translate("Delete");
			$this->desctxt = bab_translate("Name");
			$this->addtxt = bab_translate("Add");
			$this->res = $babDB->db_query("select * from ".BAB_STATS_IPAGES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by page_name asc");
			$this->count = $babDB->db_num_rows($this->res);
			$this->urlval = $url;
			$this->descval = $page;
			}

		function getnext()
			{
			global $babDB;
			static $k=0;
			if( $k < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->page = bab_toHtml($arr['page_name']);
				$this->url = bab_toHtml(bab_abbr($arr['page_url'], BAB_ABBR_FULL_WORDS, 50));
				$this->pageid = $arr['id'];
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}
		}

	$temp = new temp($url, $page);
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "pages"));
}


function statPreferences()
{
	global $babBody;
	
	class statPreferencesCls
		{
		var $updatetxt;
		var $separator;
		var $other;
		var $comma;
		var $tab;

		function statPreferencesCls()
			{
			global $babDB;
			$res = $babDB->db_query("select separatorchar from ".BAB_STATS_PREFERENCES_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$arr['separator'] = $arr['separatorchar'];
				}
			else
				{
				$babDB->db_query("insert into ".BAB_STATS_PREFERENCES_TBL." (id_user, time_interval, begin_date, end_date, separatorchar) values ('".$GLOBALS['BAB_SESS_USERID']."', '0', '', '', '".ord(",")."')");
				$arr['separator'] = ",";
				}

			$this->selected_1 = '';
			$this->selected_2 = '';
			$this->selected_3 = '';
			$this->separvalue = '';

			switch($arr['separator'] )
				{
				case 44:
					$this->selected_1 = 'selected';
					break;
				case 9:
					$this->selected_2 = 'selected';
					break;
				default:
					$this->selected_0 = 'selected';
					$this->separvalue = chr($arr['separator']);
					break;
				}

			$this->updatetxt = bab_translate("Update");
			$this->separator = bab_translate("Field separator");
			$this->other = bab_translate("Other");
			$this->comma = bab_translate("Comma");
			$this->tab = bab_translate("Tab");
			}
		}

	$temp = new statPreferencesCls();
	$babBody->babecho(	bab_printTemplate($temp,"statconf.html", "preferences"));
}

function addPage($url, $page )
{
	global $babBody, $babDB;

	if( empty($url))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide an url !!");
		return false;
		}

	if( empty($page))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( !strncasecmp($GLOBALS['babUrl'], $url, mb_strlen($GLOBALS['babUrl'])))
	{
		$url = mb_substr($url, mb_strlen($GLOBALS['babUrl']));
	}
	$babDB->db_query("insert into ".BAB_STATS_IPAGES_TBL." (page_name, page_url, id_dgowner) values ('".addslashes($page)."','".addslashes($url)."','".$babBody->currentAdmGroup."')");
}

function deletePages($pages )
{
	global $babDB;

	for( $i = 0; $i < count($pages); $i++ )
		{
		$babDB->db_query("delete from ".BAB_STATS_IPAGES_TBL." where id='".$pages[$i]."'");
		$babDB->db_query("delete from ".BAB_STATS_PAGES_TBL." where st_page_id='".$pages[$i]."'");
		}
}

function updateStatPreferences($wsepar, $separ)
{
	global $babDB;

	switch($wsepar)
		{
		case "1":
			$separ = ord(",");
			break;
		case "2":
			$separ = 9;
			break;
		default:
			if( empty($separ))
				$separ = ord(",");
			else
				$separ = ord($separ);
			break;
		}

	$babDB->db_query("update ".BAB_STATS_PREFERENCES_TBL." set separatorchar='".$separ."' where id_user='".$GLOBALS['BAB_SESS_USERID']."'");

}


function addStatBasket($baskname, $baskdesc)
{
	global $babBody, $babDB;

	if( empty($baskname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	$babDB->db_query("insert into ".BAB_STATS_BASKETS_TBL." 
	(basket_name, basket_desc, basket_author, basket_datetime, id_dgowner) 
	values (
		'".$babDB->db_escape_string($baskname)."',
		'".$babDB->db_escape_string($baskdesc)."',
		'".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."',
		now(), 
		'".$babDB->db_escape_string($babBody->currentAdmGroup)."'
		)
	");
}

function updateStatBasket($baskid, $baskname, $baskdesc)
{
	global $babBody, $babDB;

	if( empty($baskname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}


	$babDB->db_query("update ".BAB_STATS_BASKETS_TBL." set 
		basket_name='".$babDB->db_escape_string($baskname)."',
		basket_desc='".$babDB->db_escape_string($baskdesc)."' 
	where 
		id='".$babDB->db_escape_string($baskid)."'");
}

function deleteStatBasket($id )
{
	global $babDB;

	$babDB->db_query("delete from ".BAB_STATS_BASKETS_TBL." where id='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_STATS_BASKET_CONTENT_TBL." where basket_id='".$babDB->db_escape_string($id)."'");
	$babDB->db_query("delete from ".BAB_STATSBASKETS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($id)."'");
}

function deleteStatBasketContentItem()
{
	global $babDB;

	$babDB->db_query("delete from ".BAB_STATS_BASKET_CONTENT_TBL." where id='".$babDB->db_escape_string($_GET['itemid'])."' and basket_id='".$babDB->db_escape_string($_GET['baskid'])."'");
}

function addStatBasketContentItem()
{
	global $babDB;

	if( isset($_POST['ibcid']) && !empty($_POST['ibcid']))
	{

		switch($_POST['what'])
			{
			case 'top':
				$bctype = BAB_STAT_BCT_TOPIC;
				break;
			case 'art':
				$bctype = BAB_STAT_BCT_ARTICLE;
				break;
			case 'fold':
				$bctype = BAB_STAT_BCT_FOLDER;
				break;
			case 'file':
				$bctype = BAB_STAT_BCT_FILE;
				break;
			case 'for':
				$bctype = BAB_STAT_BCT_FORUM;
				break;
			case 'post':
				$bctype = BAB_STAT_BCT_POST;
				break;
			case 'faq':
				$bctype = BAB_STAT_BCT_FAQ;
				break;
			case 'faqqr':
				$bctype = BAB_STAT_BCT_QUESTION;
				break;
			default:
				$bctype = '';
				break;
			}

		if( $bctype )
		{
			$res = $babDB->db_query("select * from ".BAB_STATS_BASKET_CONTENT_TBL." where bc_id='".$_POST['ibcid']."' and basket_id='".$_POST['baskid']."' and bc_type='".$bctype."'");
			if( $res && $babDB->db_num_rows($res))
			{
				$babBody->msgerror = bab_translate("This item is already used");
				return false;
			}

			
			$ibcdesc = $_POST['ibcdesc'];

			$babDB->db_query("insert into ".BAB_STATS_BASKET_CONTENT_TBL." (basket_id, bc_description, bc_author, bc_datetime, bc_type, bc_id ) values (
			'".$babDB->db_escape_string($_POST['baskid'])."',
			'".$babDB->db_escape_string($ibcdesc)."',
			'".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."',
			now(),
			'".$babDB->db_escape_string($bctype)."',
			'".$babDB->db_escape_string($_POST['ibcid'])."'
			)");
		}
	}

	return true;
}

function updateStatBasketContentItem()
{
	global $babDB;

	if( isset($_POST['itemid']) && !empty($_POST['itemid']))
	{
		$ibcdesc = $_POST['ibcdesc'];

		$babDB->db_query("update ".BAB_STATS_BASKET_CONTENT_TBL." set bc_description='".$babDB->db_escape_string($ibcdesc)."' where id='".$babDB->db_escape_string($_POST['itemid'])."'");
	}

	return true;
}

/* main */
if ( bab_statisticsAccess() == -1 )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx)) { $idx = "conf"; }

if( isset($action))
{
	switch( $action)
	{
		case 'dpages':
		deletePages($pages);
			break;
		case 'apage':
		if(addPage($url, $desc))
		{
			$url = '';
			$desc = '';
		}
			break;
		case 'apref':
		updateStatPreferences($wsepar, $separ);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=stat");
		exit;
			break;
		case 'abask':
			addStatBasket($baskname, $baskdesc);
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
			exit;
			break;
		case 'dbask':
			deleteStatBasket($baskid);
			break;
		case 'mbask':
			updateStatBasket($baskid, $baskname, $baskdesc);
			unset($baskname);
			unset($baskdesc);
			break;
		case 'bcdel':
			deleteStatBasketContentItem();
			break;
		case 'acbask':
			addStatBasketContentItem();
			$baskid = $_POST['baskid'];
			$idx = 'baskcontent';
			break;
		case 'acubask':
			updateStatBasketContentItem();
			$baskid = $_POST['baskid'];
			$idx = 'baskcontent';
			break;
	}
}
elseif( isset($aclview))
	{
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
	exit;
	}


switch($idx)
	{

	case 'bcbrowse':
		include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
		$babBodyPopup =new babBodyPopup();
		statBrowseBasketItem();
		printBabBodyPopup();
		exit;
		exit;
		break;

	case 'baskrights':
		$babBody->title = bab_translate("List of groups");
		$macl = new macl("statconf", "bask", $baskid, "aclview");
		$macl->addtable( BAB_STATSBASKETS_GROUPS_TBL,bab_translate("Who can view this statistic basket?"));
		$macl->filter(0,0,1,0,1);
        $macl->babecho();
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		$babBody->addItemMenu("baskrights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=statconf&idx=baskrights");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}
		break;

	case 'baskdel':
		$babBody->title = bab_translate("Delete basket");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		$babBody->addItemMenu("baskdel", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=statconf&idx=baskdel");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}
		if( !isset($baskname) ) { $baskname ='';}
		if( !isset($baskdesc) ) { $baskdesc ='';}
		statDeleteBasket($baskid);
		break;

	case 'baskedit':
		$babBody->title = bab_translate("Modify basket");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		$babBody->addItemMenu("baskedit", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=statconf&idx=baskedit");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}
		statModifyBasket();
		break;

	case 'baskcedit':
		$babBody->title = bab_translate("Edit basket content");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		$babBody->addItemMenu("baskcedit", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcedit&baskid=".$baskid);
//		$babBody->addItemMenu("baskcadd", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcadd&baskid=".$baskid);
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}
		statUpdateContentBasket();
		break;

	case 'baskcadd':
		$babBody->title = bab_translate("Add basket content");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		$babBody->addItemMenu("baskcontent", bab_translate("Content"), $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcontent&baskid=".$baskid);
		$babBody->addItemMenu("baskcadd", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcadd&baskid=".$baskid);
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}
		statAddContentBasket();
		break;

	case 'baskcontent':
		$babBody->title = bab_translate("Basket content");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		$babBody->addItemMenu("baskcontent", bab_translate("Content"), $GLOBALS['babUrlScript']."?tg=statconf&idx=baskcontent");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}
		if( !isset($baskname) ) { $baskname ='';}
		if( !isset($baskdesc) ) { $baskdesc ='';}
		statContentBasket($baskid);
		break;

	case 'bask':
		$babBody->title = bab_translate("Statistics baskets");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}
		if( !isset($baskname) ) { $baskname ='';}
		if( !isset($baskdesc) ) { $baskdesc ='';}
		statBaskets($baskname, $baskdesc);
		break;

	case 'pref':
		$babBody->title = bab_translate("Preferences");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}
		statPreferences();
		break;

	case 'pages':
		$babBody->title = bab_translate("Pages");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}		
		if( !isset($url)) { $url = ""; }
		if( !isset($desc)) { $desc = ""; }
		statPages($url, $desc);
		break;

	case 'maj':
		$babBody->title = bab_translate("Update");
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			include_once $babInstallPath."utilit/statproc.php";
			}
		else
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
		break;

	default:
	case 'conf':
		$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat");
		$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
		$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
		$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
		if( $babBody->currentAdmGroup == 0 )
			{
			$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
			}		
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>