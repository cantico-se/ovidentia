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
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/mailincl.php";
include $babInstallPath."utilit/topincl.php";

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
		var $badmin;
		var $homepages;
		var $homepagesurl;
		var $checked0;
		var $checked1;
		var $deletealt;
		var $art0alt;
		var $art1alt;
		var $deletehelp;
		var $art0help;
		var $art1help;

		function temp($id)
			{
			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete articles");
			$this->art0alt = bab_translate("Make available to unregistered users home page");
			$this->art1alt = bab_translate("Make available to registered users home page");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");
			$this->art0help = bab_translate("Click on this image to make selected articles available to unregistered users home page");
			$this->art1help = bab_translate("Click on this image to make selected articles available to registered users home page");
			$this->homepages = bab_translate("Customize home pages ( Registered and unregistered users )");
			$this->badmin = bab_isUserAdministrator();

			$this->item = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$id."' and confirmed='Y' and archive='N' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$req="select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
			$r = $this->db->db_fetch_array($this->db->db_query($req));
			$this->siteid = $r['id'];
			$this->homepagesurl = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$r['id'];
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
					$this->checked0 = "checked";
				else
					$this->checked0 = "";
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='1' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					$this->checked1 = "checked";
				else
					$this->checked1 = "";
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topic&idx=viewa&item=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "articleslist"));
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
			global $BAB_SESS_USERID;
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
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topic&idx=Deletea&item=".$item."&action=Yes&items=".$items;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item;
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

function modifyCategory($id, $cat, $category, $description, $managerid, $saart, $sacom, $bnotif, $atid, $disptid, $restrict)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid category !!");
		return;
		}
	class temp
		{
		var $category;
		var $description;
		var $add;
		var $approver;
		var $approvername;
		var $langLabel;
		var $langValue;
		var $langselected;
		var $langFiles;
		var $countLangFiles;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;
		var $msie;
		var $count;
		var $topcat;
		var $modcom;
		var $yes;
		var $no;
		var $yesselected;
		var $noselected;
		var $delete;

		var $arttmpltxt;
		var $arttmplval;
		var $arttmplid;
		var $arttmplselected;
		var $arttmpl;
		var $atid;
		var $arrarttmpl;
		var $countarttmpl;

		var $disptmpltxt;
		var $disptmplval;
		var $disptmplid;
		var $disptmplselected;
		var $disptmpl;
		var $disptid;
		var $arrdisptmpl;
		var $countdisptmpl;
		var $restrictysel;
		var $restrictnsel;
		var $restricttxt;

		function temp($id, $cat, $category, $description, $managerid, $saart, $sacom, $bnotif, $atid, $disptid, $restrict)
			{
			global $babBody;
			$this->topcat = bab_translate("Topic category");
			$this->title = bab_translate("Topic");
			$this->desctitle = bab_translate("Description");
			$this->approver = bab_translate("Topic manager");
			$this->modcom = bab_translate("Approbation schema for comments");
			$this->modart = bab_translate("Approbation schema for articles");
			$this->notiftxt = bab_translate("Notify group members by mail");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Update Topic");
			$this->none = bab_translate("None");
			$this->delete = bab_translate("Delete");
			$this->arttmpltxt = bab_translate("Article's model");
			$this->disptmpltxt = bab_translate("Display template");
			$this->restricttxt = bab_translate("Articles's authors can restrict access to articles");
			$this->tgval = "topic";
			$this->item = $id;
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_TOPICS_TBL." where id='".$id."'";
			$res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($res);

			$this->cat = $this->arr['id_cat'];

			if(empty($cat))
				$this->ncat = $this->arr['id_cat'];
			else
				$this->ncat = $cat;
			if(empty($description))
				$this->description = $this->arr['description'];
			else
				$this->description = $description;
			if(empty($category))
				$this->category = $this->arr['category'];
			else
				$this->category = $category;
			if(empty($managerid))
				{
				$this->managerid = $this->arr['id_approver'];
				}
			else
				{
				$this->managerid = $managerid;
				}
			$this->managerval = bab_getUserName($this->managerid);

			if(empty($sacom))
				$this->sacom = $this->arr['idsacom'];
			else
				$this->sacom = $sacom;
			if(empty($saart))
				$this->saart = $this->arr['idsaart'];
			else
				$this->saart = $saart;

			if(empty($bnotif))
				$bnotif = $this->arr['notify'];

			if( $bnotif == "N")
				{
				$this->notifnsel = "selected";
				$this->notifysel = "";
				}
			else
				{
				$this->notifnsel = "";
				$this->notifysel = "selected";
				}

			if(empty($restrict))
				$restrict = $this->arr['restrict_access'];

			if( $restrict == "N")
				{
				$this->restrictnsel = "selected";
				$this->restrictysel = "";
				}
			else
				{
				$this->restrictnsel = "";
				$this->restrictysel = "selected";
				}

			if(empty($atid))
				$this->atid = $this->arr['article_tmpl'];
			else
				$this->atid = $atid;

			if(empty($disptid))
				$this->disptid = $this->arr['display_tmpl'];
			else
				$this->disptid = $disptid;

			$this->bdel = true;
			
			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc";
			$this->sares = $this->db->db_query($req);
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $this->db->db_num_rows($this->sares);
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";

			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	

			$file = "articlestemplate.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$arr = $tpl->getTemplates($filepath);
				for( $i=0; $i < count($arr); $i++)
					{
					if( strpos($arr[$i], "head_") !== false ||  strpos($arr[$i], "body_") !== false )
						if( count($this->arrarttmpl) == 0  || !in_array(substr($arr[$i], 5), $this->arrarttmpl ))
							$this->arrarttmpl[] = substr($arr[$i], 5);
					}
				}
			$this->countarttmpl = count($this->arrarttmpl);
			
			$file = "topicsdisplay.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$arr = $tpl->getTemplates($filepath);
				for( $i=0; $i < count($arr); $i++)
					{
					if( strpos($arr[$i], "head_") !== false ||  strpos($arr[$i], "body_") !== false )
						if( count($this->arrdisptmpl) == 0  || !in_array(substr($arr[$i], 5), $this->arrdisptmpl ))
							$this->arrdisptmpl[] = substr($arr[$i], 5);
					}
				}
			$this->countdisptmpl = count($this->arrdisptmpl);
			}


		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr2 = $this->db->db_fetch_array($this->res);
				$this->toptitle = $this->arr2['title'];
				$this->topid = $this->arr2['id'];
				if( $this->arr2['id'] == $this->ncat )
					$this->topselected = "selected";
				else
					$this->topselected = "";
				$i++;
				return true;
			}
			else
				return false;
			}
		
		function getnextschapp()
			{
			static $i = 0;
			static $j = 0;
			if( $i < $this->sacount)
				{
				$arr = $this->db->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
				if( !$j )
					{
					if( $this->said == $this->saart )
						$this->saartsel = "selected";
					else
						$this->saartsel = "";
					}
				else
					{
					if( $this->said == $this->sacom )
						$this->sacomsel = "selected";
					else
						$this->sacomsel = "";
					}
				$i++;
				return true;
				}
			else
				{
				if( $this->sacount > 0 )
					$this->db->db_data_seek($this->sares, 0);
				$i = 0;
				$j++;
				return false;
				}
			}
			
		function getnextlang()
			{
			static $i = 0;
			if($i < $this->countLangFiles)
				{
				$this->langValue = $this->langFiles[$i];
				if($this->langValue == $this->arr['lang'])
					{
					$this->langselected = 'selected';
					}
				else
					{
					$this->langselected = '';
					}
				$i++;
				return true;
				}
			return false;
			}

		function getnextarttmpl()
			{
			static $i = 0;
			if($i < $this->countarttmpl)
				{
				$this->arttmplid = $this->arrarttmpl[$i];
				$this->arttmplval = $this->arrarttmpl[$i];
				if( $this->arttmplid == $this->atid )
					$this->arttmplselected = "selected";
				else
					$this->arttmplselected = "";
				$i++;
				return true;
				}
			return false;
			}

		function getnextdisptmpl()
			{
			static $i = 0;
			if($i < $this->countdisptmpl)
				{
				$this->disptmplid = $this->arrdisptmpl[$i];
				$this->disptmplval = $this->arrdisptmpl[$i];
				if( $this->disptmplid == $this->disptid )
					$this->disptmplselected = "selected";
				else
					$this->disptmplselected = "";
				$i++;
				return true;
				}
			return false;
			}

		}

	$temp = new temp($id, $cat, $category, $description, $managerid, $saart, $sacom, $bnotif, $atid, $disptid, $restrict);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "categorycreate"));
	}

function deleteCategory($id, $cat)
	{
	global $babBody;
	
	class temp
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

		function temp($id, $cat)
			{
			$this->message = bab_translate("Are you sure you want to delete this topic");
			$this->title = bab_getCategoryTitle($id);
			$this->warning = bab_translate("WARNING: This operation will delete the topic, articles and comments"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topic&idx=Delete&category=".$id."&action=Yes&cat=".$cat;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id, $cat);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function viewArticle($article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $baCss;
		var $close;
		var $head;


		function temp($article)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->close = bab_translate("Close");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->content = bab_replace($this->arr['body']);
			$this->head = bab_replace($this->arr['head']);
			}
		}
	
	$temp = new temp($article);
	echo bab_printTemplate($temp,"topics.html", "articleview");
	}

function warnRestrictionArticle($topics)
	{
	global $babBody;

	class tempw
		{
	
		var $warningtxt;
		var $wdisplay;

		function tempw($topics)
			{
			global $babDB;
			$this->wdisplay = false;
			list($acc) = $babDB->db_fetch_row($babDB->db_query("select restrict_access from ".BAB_TOPICS_TBL." where id='".$topics."'"));
			if( $acc == 'N' )
				{
				$res = $babDB->db_query("select id from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and restriction!=''");
				if( $res && $babDB->db_num_rows($res) > 0 )
					$this->wdisplay = true;
				}
			else
				$this->wdisplay = true;

			$this->warningtxt = bab_translate("WARNING! Some articles uses access restriction. Changing access topic can make them inaccessible");
			}
		}
	
	$temp = new tempw($topics);
	$babBody->babecho( bab_printTemplate($temp,"topics.html", "articlewarning"));
	}

function updateCategory($id, $category, $description, $managerid, $cat, $saart, $sacom, $bnotif, $lang, $atid, $disptid, $restrict)
	{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	global $babBody;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a category !!");
		return false;
		}

	if( empty($managerid))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide topic manager !!");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$category = addslashes($category);
		$description = addslashes($description);
		}

	$db = $GLOBALS['babDB'];
	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_TOPICS_TBL." where id='".$id."'"));
	if( $arr['idsaart'] != $saart )
		{
		$res = $db->db_query("select * from ".BAB_ARTICLES_TBL." where id_topic='".$id."' and confirmed='N' and archive='N'");
		while( $row = $db->db_fetch_array($res))
			{
			if( $row['idfai'] != 0 )
				deleteFlowInstance($row['idfai']);
			if( $saart == 0 )
				{
				$db->db_query("update ".BAB_ARTICLES_TBL." set idfai='0', confirmed = 'Y' where id='".$row['id']."'");
				}
			else
				{
				$idfai = makeFlowInstance($saart, "art-".$row['id']);
				$db->db_query("update ".BAB_ARTICLES_TBL." set idfai='".$idfai."' where id='".$row['id']."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyArticleApprovers($row['id'], $nfusers);
				}
			}
		}

	if( $arr['idsacom'] != $sacom )
		{
		$res = $db->db_query("select * from ".BAB_COMMENTS_TBL." where id_topic='".$id."' and confirmed='N'");
		while( $row = $db->db_fetch_array($res))
			{
			if( $row['idfai'] != 0 )
				deleteFlowInstance($row['idfai']);
			if( $sacom == 0 )
				$db->db_query("update ".BAB_COMMENTS_TBL." set idfai='0', confirmed = 'Y' where id='".$row['id']."'");
			else
				{
				$idfai = makeFlowInstance($saart, "com-".$row['id']);
				$db->db_query("update ".BAB_COMMENTS_TBL." set idfai='".$idfai."' where id='".$row['id']."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyCommentApprovers($row['id'], $nfusers);
				}
			}
		}

	if (($GLOBALS['babApplyLanguageFilter'] == 'loose') and ($lang != $arr['lang']) and ($lang != '*'))
	{
		$query = "update ".BAB_ARTICLES_TBL." set lang='*' where id_topic='".$id."'";
		$db->db_query($query);
	}

	$query = "update ".BAB_TOPICS_TBL." set id_approver='".$managerid."', category='".$category."', description='".$description."', id_cat='".$cat."', idsaart='".$saart."', idsacom='".$sacom."', notify='".$bnotif."', lang='".$lang."', article_tmpl='".$atid."', display_tmpl='".$disptid."', restrict_access='".$restrict."' where id = '".$id."'";
	$db->db_query($query);

	if( $arr['id_cat'] != $cat )
		{
		$res = $db->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$cat."'");
		$arr = $db->db_fetch_array($res);
		if( isset($arr[0]))
			$ord = $arr[0] + 1;
		else
			$ord = 1;
		$db->db_query("update ".BAB_TOPCAT_ORDER_TBL." set id_parent='".$cat."', ordering='".$ord."' where id_topcat='".$id."' and type='2'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
	}


function addToHomePages($item, $homepage, $art)
{
	global $idx;

	$idx = "Articles";
	$count = count($art);

	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'");
	$arr = $db->db_fetch_array($res);
	$idsite = $arr['id'];

	$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$item."' order by date desc";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		if( $count > 0 && in_array($arr['id'], $art))
			{
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$idsite."'";
				$res2 = $db->db_query($req);
				if( !$res2 || $db->db_num_rows($res2) < 1)
				{
					$req = "insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$arr['id']. "', '" . $idsite. "', '" . $homepage. "')";
					$db->db_query($req);
				}
			}
		else
			{
				$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$idsite."'";
				$db->db_query($req);
			}

		}
}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if(!isset($idx))
	{
	$idx = "Modify";
	}

if(!isset($cat))
	{
	$db = $GLOBALS['babDB'];
	$r = $db->db_fetch_array($db->db_query("select * from ".BAB_TOPICS_TBL." where id='".$item."'"));
	$cat = $r['id_cat'];
	}

if( isset($add) )
	{
	if( isset($submit))
		{
		if(!updateCategory($item, $category, $topdesc, $managerid, $ncat, $saart, $sacom, $bnotif, $lang, $atid, $disptid, $restrict))
			$idx = "Modify";
		}
	else if( isset($topdel))
		$idx = "Delete";
	}

if( isset($aclview) )
	{
	aclUpdate($table, $item, $groups, $what);
	if( $table == BAB_TOPICSVIEW_GROUPS_TBL )
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
	else if( $table == BAB_TOPICSSUB_GROUPS_TBL )
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
	else 
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
	}

if( isset($upart) && $upart == "articles")
	{
	switch($idx)
		{
		case "homepage0":
			addToHomePages($item, 2, $hart0);
			break;
		case "homepage1":
			addToHomePages($item, 1, $hart1);
			break;
		}
	}

if( isset($action) && $action == "Yes")
	{
	if( $idx == "Delete" )
		{
		include_once $babInstallPath."utilit/delincl.php";
		bab_confirmDeleteTopic($category);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		}
	else if( $idx == "Deletea")
		{
		include_once $babInstallPath."utilit/delincl.php";
		bab_confirmDeleteArticles($items);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		}
	}

switch($idx)
	{
	case "viewa":
		viewArticle($item);
		exit;
	case "deletea":
		$babBody->title = bab_translate("Delete articles");
		deleteArticles($art, $item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list");
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item."&userid=".$userid);
		$babBody->addItemMenu("deletea", bab_translate("Delete"), "");
		break;

	case "Articles":
		$babBody->title = bab_translate("List of articles").": ".bab_getCategoryTitle($item);
		listArticles($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		break;

	case "Groups":
		$babBody->title = bab_getCategoryTitle($item);
		aclGroups("topic", "Modify", BAB_TOPICSVIEW_GROUPS_TBL, $item, "aclview");
		warnRestrictionArticle($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		break;

	case "Comments":
		$babBody->title = bab_getCategoryTitle($item);
		aclGroups("topic", "Modify", BAB_TOPICSCOM_GROUPS_TBL, $item, "aclview");
		warnRestrictionArticle($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		break;

	case "Submit":
		$babBody->title = bab_getCategoryTitle($item);
		aclGroups("topic", "Modify", BAB_TOPICSSUB_GROUPS_TBL, $item, "aclview");
		warnRestrictionArticle($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete a topic");
		deleteCategory($item, $cat);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topic&idx=Delete&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		break;

	default:
	case "Modify":
		$babBody->title = bab_translate("Modify a topic");
		modifyCategory($item, $ncat, $category, $topdesc, $managerid, $saart, $sacom, $bnotif, $atid, $disptid, $restrict);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>
