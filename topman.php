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
include_once $babInstallPath."utilit/uiutil.php";
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/artincl.php";

function listCategories()
	{
	global $babBody;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $approver;
		var $namecategory;
		var $articles;
		var $urlarticles;
		var $nbarticles;
		var $waiting;
		var $newa;
		var $newc;
		var $urlwaitinga;
		var $urlwaitingc;
		var $newac;

		function temp()
			{
			global $babBody, $BAB_SESS_USERID;
			$this->onlinearticles = bab_translate("Online article(s)");
			$this->articles = bab_translate("Article(s)");
			$this->archarticles = bab_translate("Old article(s)");
			$this->comments = bab_translate("Comment(s)");
			$this->waiting = bab_translate("Waiting");
			$this->db = $GLOBALS['babDB'];
			if( count($babBody->topman) > 0 )
				{
				$this->rescat = $this->db->db_query("SELECT DISTINCT(o.id_topcat) id, c.title title FROM ".BAB_TOPCAT_ORDER_TBL." o , ".BAB_TOPICS_CATEGORIES_TBL." c, ".BAB_TOPICS_TBL." t WHERE c.id=o.id_topcat AND t.id_cat=c.id AND t.id IN (".implode(',', $babBody->topman).") GROUP BY o.id_topcat ORDER BY o.ordering");
				$this->countcat = $this->db->db_num_rows($this->rescat);
				}
			else
				{
				$this->countcat = 0;
				}
			}

		function getnextcat()
			{
			global $babBody;
			static $j = 0;
			if( $j < $this->countcat)
				{
				$arr = $this->db->db_fetch_array($this->rescat);
				$this->catname = $arr['title'];
				$req = "select * from ".BAB_TOPICS_TBL." where id_cat='".$arr['id']."' and id IN (".implode(',', $babBody->topman).") ORDER BY category";
				$this->res = $this->db->db_query($req);
				$this->count = $this->db->db_num_rows($this->res);
				$j++;
				return true;
				}
			else
				return false;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->namecategory = viewCategoriesHierarchy_txt($this->arr['id']);
				$req = "select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and archive='N'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				list($this->newa) = $this->db->db_fetch_row($this->db->db_query("select count(id) from ".BAB_ART_DRAFTS_TBL." where id_topic='".$this->arr['id']."' and result='".BAB_ART_STATUS_WAIT."'"));

				list($this->newc) = $this->db->db_fetch_row($this->db->db_query("select count(id) as totalc from ".BAB_COMMENTS_TBL." where id_topic='".$this->arr['id']."' and confirmed='N'"));

				$this->newac = $this->newa + $this->newc;

				list($this->nbarcharticles) = $this->db->db_fetch_row($this->db->db_query("select count(id) from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and archive='Y'"));

				if( $this->nbarticles > 0 )
					{
					$this->urlarticles = $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$this->arr['id'];
					}
				elseif( $this->nbarcharticles > 0 )
					{
					$this->urlarticles = $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$this->arr['id'];
					}
				else
					{
					$this->urlarticles = '';
					}

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
	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp,"topman.html", "categorylist"));
	return $temp->count;
	}

function listArticles($id)
	{
	global $babBody;

	class temp
		{
		var $title;
		var $titlename;
		var $articleid;
		var $item;
		var $checkall;
		var $uncheckall;
		var $urltitle;

		var $db;
		var $res;
		var $count;

		var $siteid;
		var $userid;
		var $badmin;
		var $homepages;
		var $homepagesurl;

		var $checked0;
		var $checked1;
		var $deletealt;
		var $art0alt;
		var $art1alt;
		var $archivealt;
		var $deletehelp;
		var $archivehelp;
		var $art0help;
		var $art1help;

		function temp($id)
			{
			global $babBody;
			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete articles");
			$this->art0alt = bab_translate("Make available to unregistered users home page");
			$this->art1alt = bab_translate("Make available to registered users home page");
			$this->archivealt = bab_translate("Archive selected articles");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");
			$this->art0help = bab_translate("Click on this image to make selected articles available to unregistered users home page");
			$this->art1help = bab_translate("Click on this image to make selected articles available to registered users home page");
			$this->archivehelp = bab_translate("Click on this image to archive selected articles");
			$this->homepages = bab_translate("Customize home pages ( Registered and unregistered users )");
			$this->datepublicationtxt = bab_translate("Publication date");
			$this->datearchivingtxt = bab_translate("Archiving date");
			$this->previewtxt = bab_translate("Preview");
			$this->badmin = bab_isUserAdministrator();

			$this->item = $id;
			$this->siteid = $babBody->babsite['id'];

			$this->db = $GLOBALS['babDB'];
			$this->homepagesurl = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$this->siteid;
			$req = "select at.*, adt.id_article from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where at.id_topic='".$id."' and at.archive='N' order by at.ordering asc, at.date_modification desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='2' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->checked0 = "checked";
					}
				else
					{
					$this->checked0 = "";
					}
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='1' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->checked1 = "checked";
					}
				else
					{
					$this->checked1 = "";
					}
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$arr['id_topic']."&art=".$arr['id'];
				$this->propurl = $GLOBALS['babUrlScript']."?tg=topman&idx=propa&item=".$arr['id_topic']."&art=".$arr['id'];

				if( isset($arr['id_article']) && $arr['id_article'] != 0 )
					{
					$this->bupdate = true;
					$this->status = bab_translate("Article in modification");
					}
				else
					{
					$this->bupdate = false;
					$this->status = bab_translate("New article");
					}
				if( $arr['date_publication'] != '0000-00-00 00:00:00' )
					{
					$this->datepublication = bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_publication']));
					}
				else
					{
					$this->datepublication = '';
					}
				if( $arr['date_archiving'] != '0000-00-00 00:00:00' )
					{
					$this->datearchiving = bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_archiving']));
					}
				else
					{
					$this->datearchiving = '';
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "articleslist"));
	}

function listOldArticles($id)
	{
	global $babBody;

	class temp
		{
		var $title;
		var $titlename;
		var $articleid;
		var $item;
		var $checkall;
		var $uncheckall;
		var $urltitle;

		var $db;
		var $res;
		var $count;

		var $archivealt;
		var $archivehelp;

		var $deletealt;
		var $deletehelp;

		function temp($id)
			{
			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->archivealt = bab_translate("Move selected articles from archive");
			$this->archivehelp = bab_translate("Click on this image to move out selected articles from archive");
			$this->deletealt = bab_translate("Delete articles");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");

			$this->item = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$id."' and archive='Y' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$arr['id_topic']."&art=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "oldarticleslist"));
	}

function viewArticle($article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $head;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $babMeta;
		var $babCss;
		var $close;
		var $altbg = false;


		function temp($article)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta = bab_printTemplate($this,"config.html", "babMeta");
			$this->close = bab_translate("Close");
			$this->deletetxt = bab_translate("Delete");
			$this->attachmentxt = bab_translate("Associated documents");
			$this->commentstxt = bab_translate("Comments");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if( bab_isUserTopicManager($this->arr['id_topic']))
				{
				$this->content = bab_replace($this->arr['body']);
				$this->head = bab_replace($this->arr['head']);
				}
			else
				{
				$this->content = "";
				$this->head = bab_translate("Access denied");
				}

			$this->resf = $this->db->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$article."'");
			$this->countf = $this->db->db_num_rows($this->resf);

			if( $this->countf > 0 )
				{
				$this->battachments = true;
				}
			else
				{
				$this->battachments = false;
				}

			$this->rescom = $this->db->db_query("select * from ".BAB_COMMENTS_TBL." where id_article='".$article."' and confirmed='Y' order by date desc");
			$this->countcom = $this->db->db_num_rows($this->rescom);
			}

		function getnextdoc()
			{
			global $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $this->db->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=topman&idx=getf&item=".$this->arr['id_topic']."&idf=".$arr['id'];
				$this->docname = $arr['name'];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextcom()
			{
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $this->db->db_fetch_array($this->rescom);
				$this->altbg = !$this->altbg;
				$this->commentdate = bab_strftime(bab_mktime($arr['date']));
				$this->authorname = $arr['name'];
				$this->commenttitle = $arr['subject'];
				$this->commentbody = bab_replace($arr['message']);
				$this->delcomurl = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&delc=com&item=".$this->arr['id_topic']."&art=".$this->arr['id']."&idc=".$arr['id'];
				$i++;
				return true;
				}
			else
				{
				$this->db->db_data_seek($this->rescom,0);
				$i=0;
				return false;
				}
			}
		}
	
	$temp = new temp($article);
	echo bab_printTemplate($temp,"topman.html", "articleview");
	}

function deleteArticles($art, $item)
	{
	global $babBody, $idx;

	class tempa
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function tempa($art, $item)
			{
			$this->message = bab_translate("Are you sure you want to delete those articles");
			$this->title = "";
			$items = "";
			$db = $GLOBALS['babDB'];
			for($i = 0; $i < count($art); $i++)
				{
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$art[$i]."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". $arr['title'];
					$items .= $arr['id'];
					}
				if( $i < count($art) -1)
					$items .= ",";
				}
			$this->warning = bab_translate("WARNING: This operation will delete articles and their comments"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topman&idx=Deletea&item=".$item."&action=Yes&items=".$items;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item;
			$this->no = bab_translate("No");
			}
		}

	if( count($item) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		listArticles($item);
		$idx = "Articles";
		return;
		}
	$tempa = new tempa($art, $item);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	}


function orderArticles($id)
	{
	global $babBody;
	class temp
		{		
		var $sorta;
		var $sortd;
		var $topicid;

		function temp($id)
			{
			global $babBody, $BAB_SESS_USERID;
			$this->topicid = $id;
			$this->toplisttxt = "---- ".bab_translate("Top")." ----";
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->sorta = bab_translate("Sort ascending");
			$this->sortd = bab_translate("Sort descending");
			$this->create = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			$req = "select id, title from ".BAB_ARTICLES_TBL." where archive='N' and id_topic='".$id."' order by ordering asc, date_modification desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->articletitle = $arr['title'];
				$this->articleid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "articlesorder"));
	}


function viewArticleProperties($item, $idart)
	{
	global $babBodyPopup;
	class temp
		{

		function temp($item, $idart)
			{
			global $babBodyPopup, $babBody, $babDB, $BAB_SESS_USERID, $topicid;
			$this->access = false;

			$req = "select at.id, at.title, at.id_topic, at.date_publication, at.date_archiving, at.restriction, count(aft.id) as totalf from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id='".$idart."' group by aft.id_article";
			$res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($res);
			if( $this->count > 0 )
				{
				$this->access = true;
				$this->idart = $idart;
				$arrart = $babDB->db_fetch_array($res);
				$this->submittxt = bab_translate("Update");
				$this->topictxt = bab_translate("Topic");
				$this->titletxt = bab_translate("Title");
				$this->idart = $idart;
				$this->item = $item;
				$this->idtopicsel = $arrart['id_topic'];
				$this->steptitle = viewCategoriesHierarchy_txt($arrart['id_topic']);

				$this->draftname = $arrart['title'];

				if( count($babBody->topman) > 0 )
					{
					$this->restopics = $babDB->db_query("select tt.id, tt.category, tt.restrict_access, tct.title, tt.notify from ".BAB_TOPICS_TBL." tt LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." tct on tct.id=tt.id_cat where tt.id IN(".implode(',', $babBody->topman).")");
					$this->counttopics = $babDB->db_num_rows($this->restopics);

					if( $arrart['totalf'] > 0 )
						{
						$this->warnfilemessage = bab_translate("Warning! If you change topic, you can lost associated documents");
						}
					$this->bshowtopics = true;
					}
				else
					{
					$this->counttopics = 0;
					$this->warnfilemessage = '';
					$this->bshowtopics = false;
					}

				$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'";
				$res = $babDB->db_query($req);
				$this->elapstime = 5;
				$this->ampm = false;
				if( $res && $babDB->db_num_rows($res))
					{
					$rr = $babDB->db_fetch_array($res);
					if( $rr['ampm'] == "Y")
						{
						$this->ampm = true;
						}
					}


				$this->datepubtitle = bab_translate("Date of publication");
				$this->datepuburl = $GLOBALS['babUrlScript']."?tg=month&callback=datePub&ymin=0&ymax=2";
				$this->datepubtxt = bab_translate("Publication date");
				$this->dateendurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=2";
				$this->dateendtxt = bab_translate("Archiving date");
				$this->invaliddate = bab_translate("ERROR: End date must be older");
				$this->invaliddate = str_replace("'", "\'", $this->invaliddate);
				$this->invaliddate = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate);
				$this->cdateecheck = '';
				if( $arrart['date_publication'] != '0000-00-00 00:00:00' )
					{
					$this->cdatepcheck = 'checked';
					$rr = explode(" ", $arrart['date_publication']);
					$rr0 = explode("-", $rr[0]);
					$rr1 = explode(":", $rr[1]);
					$this->yearpub = $rr0[0];
					$this->monthpub = $rr0[1];
					$this->daypub = $rr0[2];
					$this->timepub = $rr1[0].":".$rr1[1];
					}
				else
					{
					$this->cdatescheck = '';
					$this->yearpub = date("Y");
					$this->monthpub = date("n");
					$this->daypub = date("j");
					$this->timepub = "00:00";
					}

				if( $arrart['date_archiving'] != '0000-00-00 00:00:00' )
					{
					$this->cdateecheck = 'checked';
					$rr = explode(" ", $arrart['date_archiving']);
					$rr0 = explode("-", $rr[0]);
					$rr1 = explode(":", $rr[1]);
					$this->yearend = $rr0[0];
					$this->monthend = $rr0[1];
					$this->dayend = $rr0[2];
					$this->timeend = $rr1[0].":".$rr1[1];
					}
				else
					{
					$this->cdateecheck = '';
					$this->yearend = date("Y");
					$this->monthend = date("n");
					$this->dayend = date("j");
					$this->timeend = "00:00";
					}

				$this->daysel = $this->daypub;
				$this->monthsel = $this->monthpub;
				$this->yearsel = $this->yearpub - date("Y") + 1;
				$this->timesel = $this->timepub;

				if( $arrart['restriction'] == '' )
					{
					$this->restrictaccess = true;
					$this->restrictiontitletxt = bab_translate("Access restriction");
					$this->operatortxt = bab_translate("Operator");
					$this->ortxt = bab_translate("Or");
					$this->andtxt = bab_translate("And");
					$this->groupstxt = bab_translate("Groups");
					$this->restrictiontxt = bab_translate("Access restriction");
					$this->norestricttxt = bab_translate("No restriction");
					$this->yesrestricttxt = bab_translate("Groups");
					$this->resgrp = $babDB->db_query("select * from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_object='".$arrart['id_topic']."' and id_group > '2'");
					if( $this->resgrp )
						{
						$this->countgrp = $babDB->db_num_rows($this->resgrp);
						}
					else
						{
						$this->countgrp = 0;
						}
					if( strchr($arrart['restriction'], "&"))
						{
						$this->arrrest = explode('&', $arrart['restriction']);
						$this->operatororysel = '';
						$this->operatorornsel = 'selected';
						}
					else if( strchr($arrart['restriction'], ","))
						{
						$this->arrrest = explode(',', $arrart['restriction']);
						}
					else
						$this->arrrest = array($arrart['restriction']);

					if( empty($arr['restriction']))
						{
						$this->norestrictsel = 'selected';
						$this->yesrestrictsel = '';
						}
					else
						{
						$this->norestrictsel = '';
						$this->yesrestrictsel = 'selected';
						}
					}
				}
			else
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}

		function getnexttopic()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counttopics)
				{
				$arr = $babDB->db_fetch_array($this->restopics);
				$this->topicname = $arr['category'];
				$this->categoryname = $arr['title'];
				$this->idtopic = $arr['id'];
				if( $this->idtopicsel == $arr['id'] )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextgroup()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $babDB->db_fetch_array($this->resgrp);
				$this->grpid = $arr['id_group'];
				$this->grpname = bab_getGroupName($arr['id_group']);
				if( in_array($this->grpid, $this->arrrest))
					{
					$this->grpcheck = 'checked';
					}
				else
					{
					$this->grpcheck = '';
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}


		function getnextday()
			{
			static $i = 1, $p=0;

			if( $i <= date("t"))
				{
				$this->dayid = $i;
				if( $this->daysel == $i)
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				if( $p == 0 )
					{
					$this->daysel = $this->dayend;
					$p++;
					}
				$i = 1;
				return false;
				}

			}

		function getnextmonth()
			{
			global $babMonths;
			static $i = 1, $p;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = $babMonths[$i];
				if( $this->monthsel == $i)
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}

				$i++;
				return true;
				}
			else
				{
				if( $p == 0)
					{
					$this->monthsel = $this->monthend;
					$p++;
					}
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0, $p;
			if( $i < 3)
				{
				$this->yearid = $i+1;
				$this->yearidval = date("Y") + $i;
				if( $this->yearsel == $this->yearid )
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				if( $p == 0 )
					{
					$this->yearsel = $this->yearend - date("Y") + 1;
					$p++;
					}
				$i = 0;
				return false;
				}

			}

		function getnexttime()
			{

			static $i = 0, $p = 0;

			if( $i < 1440/$this->elapstime)
				{
				$this->timeval = sprintf("%02d:%02d", ($i*$this->elapstime)/60, ($i*$this->elapstime)%60);
				if( $this->ampm )
					{
					$this->time = bab_toAmPm($this->timeval);
					}
				else
					{
					$this->time = $this->timeval;
					}
				if( $this->timeval == $this->timesel )
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				if( $p == 0)
					{
					$this->timesel = $this->timeend;
					$p++;
					}
				$i = 0;
				return false;
				}

			}
		}

	$temp = new temp($item, $idart);
	$babBodyPopup->babecho(bab_printTemplate($temp, "topman.html", "propertiesarticle"));
	}


function siteHomePage0($id)
	{

	global $babBody;
	class temp0
		{
		var $create;
	
		var $moveup;
		var $movedown;

		var $id;
		var $arr = array();
		var $db;
		var $res;

		var $listhometxt;
		var $listpagetxt;
		var $title;

		function temp0($id)
			{
			$this->title = bab_translate("Unregistered users home page");
			$this->listhometxt = bab_translate("---- Proposed Home articles ----");
			$this->listpagetxt = bab_translate("---- Home page articles ----");
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->create = bab_translate("Modify");
			$this->id = $id;

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='2' and id_site='$id' and ordering='0'";
			$this->reshome0 = $this->db->db_query($req);
			$this->counthome0 = $this->db->db_num_rows($this->reshome0);
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='2' and id_site='$id' and ordering!='0' order by ordering asc";
			$this->respage0 = $this->db->db_query($req);
			$this->countpage0 = $this->db->db_num_rows($this->respage0);
			}

		function getnexthome0()
			{
			static $i = 0;
			if( $i < $this->counthome0 )
				{
				$arr = $this->db->db_fetch_array($this->reshome0 );
				$this->home0id = $arr['id_article'];
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$this->home0id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->home0val = $arr['title'];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextpage0()
			{
			static $k = 0;
			if( $k < $this->countpage0 )
				{
				$arr = $this->db->db_fetch_array($this->respage0 );
				$this->page0id = $arr['id_article'];
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$this->page0id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->page0val = $arr['title'];
				$k++;
				return true;
				}
			else
				return false;
			}
		}

	$temp0 = new temp0($id);
	$babBody->babecho(	bab_printTemplate($temp0, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp0, "topman.html", "sitehomepage0"));
	}

function siteHomePage1($id)
	{

	global $babBody;
	class temp1
		{
		var $create;
	
		var $moveup;
		var $movedown;

		var $id;
		var $arr = array();
		var $db;
		var $res;

		var $listhometxt;
		var $listpagetxt;
		var $title;

		function temp1($id)
			{
			$this->title = bab_translate("Registered users home page");
			$this->listhometxt = bab_translate("---- Proposed Home articles ----");
			$this->listpagetxt = bab_translate("---- Home page articles ----");
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->create = bab_translate("Modify");
			$this->id = $id;

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='1' and id_site='$id' and ordering='0'";
			$this->reshome1 = $this->db->db_query($req);
			$this->counthome1 = $this->db->db_num_rows($this->reshome1);
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='1' and id_site='$id' and ordering!='0' order by ordering asc";
			$this->respage1 = $this->db->db_query($req);
			$this->countpage1 = $this->db->db_num_rows($this->respage1);
			}

		function getnexthome1()
			{
			static $i = 0;
			if( $i < $this->counthome1 )
				{
				$arr = $this->db->db_fetch_array($this->reshome1 );
				$this->home1id = $arr['id_article'];
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$this->home1id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->home1val = $arr['title'];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextpage1()
			{
			static $k = 0;
			if( $k < $this->countpage1 )
				{
				$arr = $this->db->db_fetch_array($this->respage1 );
				$this->page1id = $arr['id_article'];
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$this->page1id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->page1val = $arr['title'];
				$k++;
				return true;
				}
			else
				return false;
			}
		}

	$temp0 = new temp1($id);
	$babBody->babecho(	bab_printTemplate($temp0, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp0, "topman.html", "sitehomepage1"));
	}

function addToHomePages($item, $homepage, $art)
{
	global $babBody, $idx;

	$idx = "Articles";
	$count = count($art);

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$item."' order by date desc";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		if( $count > 0 && in_array($arr['id'], $art))
			{
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$babBody->babsite['id']."'";
				$res2 = $db->db_query($req);
				if( !$res2 || $db->db_num_rows($res2) < 1)
				{
					$req = "insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$arr['id']. "', '" . $babBody->babsite['id']. "', '" . $homepage. "')";
					$db->db_query($req);
					notifyArticleHomePage(bab_getCategoryTitle($item), $arr['title'], $homepage, $homepage);

				}
			}
		else
			{
				$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$babBody->babsite['id']."'";
				$db->db_query($req);
			}

		}
}

function archiveArticles($item, $aart)
{
	$cnt = count($aart);
	$db = $GLOBALS['babDB'];
	for($i = 0; $i < $cnt; $i++)
		{
		$db->db_query("update ".BAB_ARTICLES_TBL." set archive='Y' where id='".$aart[$i]."'");
		$db->db_query("delete from ".BAB_HOMEPAGES_TBL." where id_article='".$aart[$i]."'");
		}
}

function unarchiveArticles($item, $aart)
{
	global $idx;

	$idx = "Articles";
	$cnt = count($aart);
	$db = $GLOBALS['babDB'];
	for($i = 0; $i < $cnt; $i++)
		{
		$db->db_query("update ".BAB_ARTICLES_TBL." set archive='N' where id='".$aart[$i]."'");
		}
}

function saveOrderArticles($id, $listarts)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];
	
	$db->db_query("update ".BAB_ARTICLES_TBL." set ordering='0' where id_topic='".$id."'");
	for($i=0; $i < count($listarts); $i++)
		{
		$db->db_query("update ".BAB_ARTICLES_TBL." set ordering='".($i+1)."' where id='".$listarts[$i]."'");
		}
	}


function saveArticleProperties()
{
	global $babBody, $babDB, $BAB_SESS_USERID, $idart, $item, $topicid, $cdatep, $yearbegin, $yearpub, $monthpub, $daypub, $timepub, $cdatee, $yearend, $yearend, $monthend, $dayend, $timeend, $restriction;

	if( isset($cdatep) || isset($cdatee) || isset($topicid) || isset($restriction))
	{
	$res = $babDB->db_query("select at.id_topic, count(aft.id) as totalf from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id='".$idart."' group by aft.id_article");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arrreq = array();

		$arrart = $babDB->db_fetch_array($res);
		if( isset($cdates)) 
			{
			$date_pub = sprintf("%04d-%02d-%02d %s:00", date("Y") + $yearpub - 1, $monthpub, $daypub, $timepub);
			$arrreq[] = "date_publication='".$date_pub."'";
			}

		if( isset($cdatee)) 
			{
			$date_end = sprintf("%04d-%02d-%02d %s:00", date("Y") + $yearend - 1, $monthend, $dayend, $timeend);
			$arrreq[] = "date_archiving='".$date_end."'";
			}

		if( isset($restriction))
			{
			if( $restriction == "1" && isset($grpids) && count($grpids) > 0)
				{
				$restriction = implode($operator, $grpids);
				}
			else
				{
				$restriction = '';
				}

			$arrreq[] = "restriction='".$restriction."'";
			}
		
		if( $arrart['id_topic'] != $topicid )
			{
			$babDB->db_query("update ".BAB_COMMENTS_TBL." set id_topic='".$topicid."' where id_article='".$idart."' and id_topic='".$topicid."'");

			if( $arrart['totalf'] >  0 )
				{
				list($allowattach) = $babDB->db_fetch_array($babDB->db_query("select allow_attachments from ".BAB_TOPICS_TBL." where id='".$topicid."'"));
				if( $allowattach ==  'N' )
					{
					include_once $GLOBALS['babInstallPath']."utilit/artincl.php";
					bab_deleteArticleFiles($idart);
					}
				}
			$arrreq[] = "id_topic='".$topicid."'";
			}

		if( count($arrreq) > 0 )
			{
			$req = "update ".BAB_ARTICLES_TBL." set ".implode(',', $arrreq)." where id='".$idart."'";
			$babDB->db_query($req);
			}
		}
	}
}

function siteUpdateHomePage0($item, $listpage0)
	{
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_HOMEPAGES_TBL." set ordering='0' where id_site='".$item."' and id_group='2'";
	$res = $db->db_query($req);

	for($i=0; $i < count($listpage0); $i++)
		{
		$req = "update ".BAB_HOMEPAGES_TBL." set ordering='".($i + 1)."' where id_group='2' and id_site='".$item."' and id_article='".$listpage0[$i]."'";
		$res = $db->db_query($req);
		}
	return true;
	}

function siteUpdateHomePage1($item, $listpage1)
	{
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_HOMEPAGES_TBL." set ordering='0' where id_site='".$item."' and id_group='1'";
	$res = $db->db_query($req);

	for($i=0; $i < count($listpage1); $i++)
		{
		$req = "update ".BAB_HOMEPAGES_TBL." set ordering='".($i + 1)."' where id_group='1' and id_site='".$item."' and id_article='".$listpage1[$i]."'";
		$res = $db->db_query($req);
		}
	return true;
	}


function topman_init($item)
{
	global $babBody, $babDB;
	$arrinit = array();

	$res = $babDB->db_query("select count(id) from ".BAB_ARTICLES_TBL." where id_topic='".$item."' and archive='Y'");
	list($arrinit['nbarchive']) = $babDB->db_fetch_row($res);

	$res = $babDB->db_query("select count(id) from ".BAB_ARTICLES_TBL." where id_topic='".$item."' and archive='N'");
	list($arrinit['nbonline']) = $babDB->db_fetch_row($res);

	$arrinit['hman'] = bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']);

	return $arrinit;
}



/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if( isset($item) && bab_isUserTopicManager($item) )
{
	$manager = true;
}
else
{
	$manager = false;
}


if( isset($upart) && $upart == "articles" && $manager)
	{
	switch($idx)
		{
		case "homepage0":
			addToHomePages($item, 2, $hart0);
			break;
		case "homepage1":
			addToHomePages($item, 1, $hart1);
			break;
		case "unarch":
			unarchiveArticles($item, $aart);
			break;
		}
	}
elseif( isset($action) && $action == "Yes" && $manager)
	{
	if( $idx == "Deletea")
		{
		include_once $babInstallPath."utilit/delincl.php";
		bab_confirmDeleteArticles($items);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
		}
	}
elseif( isset($delf) && $delf == "file" && $manager)
	{
	delDocumentArticle($idf);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$item."&art=".$art);
	}
elseif( isset($delc) && $delc== "com" && $manager)
	{
	include_once $babInstallPath."utilit/delincl.php";
	bab_deleteComment($idc);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$item."&art=".$art);
	}
elseif( isset($update)  && $manager)
	{
	if( $update == "order" )
		{
		saveOrderArticles($item, $listarts);
		}
	elseif( $update == "propa" )
		{
		saveArticleProperties($item, $idart);
		$idx='unload';
		$popupmessage = bab_translate("Update done");
		$refreshurl = $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item;
		}
	}
elseif( isset($updateh)  && bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']))
	{
	if( $updateh == "homepage0" )
		{
		if( !isset($listpage0)) { $listpage0 = array();}
		siteUpdateHomePage0($item, $listpage0);
		}
	else if( $updateh == "homepage1" )
		{
		if( !isset($listpage1)) { $listpage1 = array();}
		siteUpdateHomePage1($item, $listpage1);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=list");
	}

switch($idx)
	{
	case "unload":
		if( !isset($popupmessage)) { $popupmessage ='';}
		if( !isset($refreshurl)) { $refreshurl ='';}
		popupUnload($popupmessage, $refreshurl);
		exit;
	case "getf":
		if( $manager )
		{
		bab_getDocumentArticle( $idf );
		}
		else
		{
			echo bab_translate("Access denied");
		}
		exit;
		break;

	case "viewa":
		if( $manager )
		{
		viewArticle($art);
		}
		else
		{
			echo bab_translate("Access denied");
		}
		exit;
	
	case "propa":
		if( $manager )
		{
			$babBodyPopup = new babBodyPopup();
			$babBodyPopup->title = bab_translate("Article properties");
			viewArticleProperties( $item, $art );
			printBabBodyPopup();
			exit;
		}
		else
		{
			echo bab_translate("Access denied");
		}
		exit;
		break;
	case "deletea":
		$arrinit = topman_init($item);
		if( $manager && $arrinit['nbonline'] > 0)
		{
		$babBody->title = bab_translate("Delete articles");
		deleteArticles($art, $item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
		$babBody->addItemMenu("deletea", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topman&idx=deletea&art=".$art);
		if( $arrinit['nbarchive'] > 0)
			{
			$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item);
			}
		if( $arrinit['hman'] > 0)
			{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "alist":
		$arrinit = topman_init($item);
		if( $manager  && $arrinit['nbarchive'] > 0)
		{
		$babBody->title = bab_translate("List of old articles").": ".bab_getCategoryTitle($item);
		listOldArticles($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		if( $arrinit['nbonline'] > 0 )
			{
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
			}
		$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item);
		if( $arrinit['hman'] > 0)
			{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "archive":
		if( $manager )
		{
		archiveArticles($item, $aart);
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		/* no break; */
	case "Articles":
		$arrinit = topman_init($item);
		if( $manager && $arrinit['nbonline'] > 0)
		{
		$babBody->title = bab_translate("List of articles").": ".bab_getCategoryTitle($item);
		listArticles($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
		$babBody->addItemMenu("ord", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topman&idx=ord&item=".$item);
		if( $arrinit['nbarchive'] > 0)
			{
			$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item);
			}
		if( $arrinit['hman'] > 0)
			{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "ord":
		$arrinit = topman_init($item);
		if( $manager && $arrinit['nbonline'] > 0)
		{
		$babBody->title = bab_translate("Order of articles").": ".bab_getCategoryTitle($item);
		orderArticles($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
		$babBody->addItemMenu("ord", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topman&idx=ord&item=".$item);
		if($arrinit['nbarchive'] > 0)
			{
			$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item);
			}
		if( $arrinit['hman'] > 0)
			{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "hpriv":
		if( $manager || bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']))
		{
		$babBody->title = bab_translate("Registered users home page for site").": ".$babBody->babsite['name'];
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		if( bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']) )
			{
			siteHomePage1($ids);
			$babBody->addItemMenu("hpriv", bab_translate("Private"),$GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			$babBody->addItemMenu("hpub", bab_translate("Public"),$GLOBALS['babUrlScript']."?tg=topman&idx=hpub&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "hpub":
		if( $manager || bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']))
		{
		$babBody->title = bab_translate("Unregistered users home page for site").": ".$babBody->babsite['name'];
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		if( bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']) )
			{
			siteHomePage0($ids);
			$babBody->addItemMenu("hpriv", bab_translate("Private"),$GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			$babBody->addItemMenu("hpub", bab_translate("Public"),$GLOBALS['babUrlScript']."?tg=topman&idx=hpub&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	default:
	case "list":
		$babBody->title = bab_translate("List of managed topics");
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		if( listCategories() == 0 )
			{
			$babBody->title = bab_translate("There is no topic");
			}
		if( bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']) )
		{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
		}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>