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
/**
* @internal SEC1 NA 08/12/2006 FULL
*/
include 'base.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/mailincl.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/artincl.php';
require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';


function listDrafts()
	{
	global $babBody;
	class listDraftsCls
		{
		var $name;
		var $nametxt;
		var $datesubtxt;
		var $datesub;
		var $statustxt;
		var $status;
		var $urlname;
		var $count;
		var $res;
		var $addtxt;
		var $urladd;
		var $edittxt;
		var $deletetxt;
		var $editurl;
		var $deleteurl;
		var $nbtrash;
		var $previewtxt;
		var $previewurl;
		var $submittxt;
		var $bsubmit;
		var $bsubmiturl;

		public function __construct()
			{
			global $babDB;
			$this->nametxt = bab_translate("Articles");
			$this->datesubtxt = bab_translate("Submission");
			$this->statustxt = bab_translate("Status");
			$this->proptxt = bab_translate("Properties");
			$this->deletetxt = bab_translate("Delete");
			$this->previewtxt = bab_translate("Preview");
			$this->addtxt = bab_translate("Create a new article");
			$this->modtxt = bab_translate("Modify an existing article");
			$this->attachmenttxt = bab_translate("Attachments");
			$this->submittxt = bab_translate("Submit");
			$this->t_modify = bab_translate("Modify");
			$this->js_confirm_submit = bab_translate("Do you really want to submit")."?";
			$this->urladd = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=s0");
			$this->urlmod = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=s00");
			$req = "select adt.*, count(adft.id) as total from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and adt.trash !='Y' and adt.idfai='0' and adt.result='".BAB_ART_STATUS_DRAFT."' GROUP BY adt.id order by date_modification desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		public function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->urlname = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=s1&idart=".$arr['id']);
				$this->propurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=s3&idart=".$arr['id']);
				$this->deleteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=movet&idart=".$arr['id']);
				$this->previewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id']);
				$this->name = bab_toHtml($arr['title']);
				$this->datesub = $arr['date_submission'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_submission']), true);
				$this->datesub = bab_toHtml($this->datesub);
				$this->datepub = $arr['date_publication'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_publication']), true);
				$this->datepub = bab_toHtml($this->datepub);
				$this->datearch = $arr['date_archiving'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_archiving']), true);
				$this->datearch = bab_toHtml($this->datearch);
				if( $arr['total'] > 0 )
					{
					$this->attachment = true;
					}
				else
					{
					$this->attachment = false;
					}
				if( $arr['id_topic'] != 0 && !empty($arr['title']) && !empty($arr['head']))
					{
					$this->bsubmit = true;
					$this->bsubmiturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=sub&idart=".$arr['id']."");
					}
				else
					{
					$this->bsubmit = false;
					}
				if( $arr['id_article'] != 0 )
					{
					$this->bupdate = true;
					$this->status = bab_translate("Article in modification");
					}
				else
					{
					$this->bupdate = false;
					$this->status = bab_translate("New article");
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new listDraftsCls();
	$babBody->babecho( bab_printTemplate($temp, "artedit.html", "draftslist"));
	}


function listSubmitedArticles()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $nametxt;
		var $datesubtxt;
		var $datesub;
		var $urlname;
		var $count;
		var $res;
		var $emptytxt;
		var $urlempty;
		var $edittxt;
		var $deletetxt;
		var $editurl;
		var $deleteurl;
		var $previewtxt;
		var $previewurl;
		var $statustxt;

		function temp()
			{
			global $babDB;
			$this->nametxt = bab_translate("Articles");
			$this->datesubtxt = bab_translate("Submission");
			$this->proptxt = bab_translate("Properties");
			$this->deletetxt = bab_translate("Delete");
			$this->previewtxt = bab_translate("Preview");
			$this->statustxt = bab_translate("Status");
			$this->emptytxt = bab_translate("Empty");
			$this->restoretxt = bab_translate("Restore");
			$this->attachmenttxt = bab_translate("Attachments");
			$this->notestxt = bab_translate("Notes");
			$this->urladd = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=new");
			$req = "select adt.*, count(adft.id) as totalf, count(adnt.id) as totaln from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id  left join ".BAB_ART_DRAFTS_NOTES_TBL." adnt on adnt.id_draft=adt.id where adt.id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and adt.trash !='Y' and adt.result!='".BAB_ART_STATUS_DRAFT."' GROUP BY adt.id order by date_submission desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->urlname = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=propa&idart=".$arr['id']);
				$this->propurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=prop&idart=".$arr['id']);
				$this->deleteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=delt&idart=".$arr['id']);
				$this->previewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id']);
				$this->restoreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=rests&idart=".$arr['id']);
				$this->name = bab_toHtml($arr['title']);
				$this->datesub = $arr['date_submission'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_submission']), true);
				$this->datesub = bab_toHtml($this->datesub);

				if( $arr['result'] == BAB_ART_STATUS_WAIT )
					{
					$this->bdelete = false;
					}
				else
					{
					$this->bdelete = true;
					}
				if( $arr['totalf'] > 0 )
					{
					$this->attachment = true;
					}
				else
					{
					$this->attachment = false;
					}
				if( $arr['totaln'] > 0 )
					{
					$this->bnotes = true;
					}
				else
					{
					$this->bnotes = false;
					}
				switch($arr['result'])
					{
					case BAB_ART_STATUS_WAIT:
						$this->status = bab_translate("Waiting");
						break;
					case BAB_ART_STATUS_OK:
						$this->status = bab_translate("Accepted");
						break;
					case BAB_ART_STATUS_NOK:
						$this->status = bab_translate("Refused");
						break;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho( bab_printTemplate($temp, "artedit.html", "submitedarticleslist"));
	}

function listDraftsInTrash()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $nametxt;
		var $urlname;
		var $datecreatetxt;
		var $datecreate;
		var $datemodifytxt;
		var $datemodify;
		var $count;
		var $res;
		var $addtxt;
		var $urladd;
		var $restoretxt;
		var $restoreurl;
		var $previewtxt;
		var $previewurl;

		function temp()
			{
			global $babDB;
			$this->nametxt = bab_translate("Articles");
			$this->datecreatetxt = bab_translate("Creation");
			$this->datemodifytxt = bab_translate("Modification");
			$this->edittxt = bab_translate("Edit");
			$this->restoretxt = bab_translate("Restore");
			$this->previewtxt = bab_translate("Preview");
			$this->addtxt = bab_translate("Empty");
			$this->urladd = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=empty");
			$req = "select * from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and trash !='N' order by date_modification desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->urlname = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=view&idart=".$arr['id']);
				$this->restoreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=restore&idart=".$arr['id']);
				$this->previewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id']);
				$this->name = bab_toHtml($arr['title']);
				$this->datecreate = bab_toHtml(bab_shortDate(bab_mktime($arr['date_creation']), true));
				$this->datemodify = bab_toHtml(bab_shortDate(bab_mktime($arr['date_modification']), true));
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho( bab_printTemplate($temp, "artedit.html", "trashlist"));
	}



function propertiesArticle($idart)
{
	global $babBody;
	class temp
		{
		var $arttxt;
		var $sContent;
		
		function temp($idart)
			{
			global $babDB;
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->idart = bab_toHtml($idart);
				$this->arttxt = bab_translate("Article");
				$this->pathtxt = bab_translate("Path");
				$this->authortxt = bab_translate("Author");
				$this->confirmtxt = bab_translate("Confirm");
				$this->commenttxt = bab_translate("Comment");
				$this->yes = bab_translate("Yes");
				$this->no = bab_translate("No");
				$this->closetxt = bab_translate("Close");

				$this->datepubtxt = bab_translate("Publication date");
				$this->datearchtxt = bab_translate("Archiving date");
				$this->hpages0txt = bab_translate("Proposed to unregistered users home page");
				$this->hpages1txt = bab_translate("Proposed to registered users home page");
				$this->updatetxt = bab_translate("Update");

				$this->arttitle = bab_toHtml($arr['title']);
				$this->pathname = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->author = bab_toHtml(bab_getUserName($arr['id_author']));
				$this->datepub = $arr['date_publication'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_publication']), true);
				$this->datepub = bab_toHtml($this->datepub);
				$this->datearch = $arr['date_archiving'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_archiving']), true);
				$this->datearch = bab_toHtml($this->datearch);

				if( $arr['hpage_public'] == 'Y')
					{
					$this->hpages0 = $this->yes;
					}
				else
					{
					$this->hpages0 = $this->no;
					}
				if( $arr['hpage_private'] == 'Y')
					{
					$this->hpages1 = $this->yes;
					}
				else
					{
					$this->hpages1 = $this->no;
					}
				if( $arr['result'] == BAB_ART_STATUS_WAIT && $arr['idfai'] != 0 )
					{
					/* show waiting approvers */
					}
				}
			else
				{
				echo bab_translate("Access denied");
				}
			}

		}

	$temp = new temp($idart);
	echo bab_printTemplate($temp, "artedit.html", "propertiesarticles");
}





function showTopicTree($actionType, $selectedTopicId)
{
	class FormTemplate
	{
		var $idart;
		var $rfurl;
		var $next_idx;
		var $t_no_topic;

		var $bcontent;
		var $title;
		var $headtext;
		var $bodytext;
		var $lang;

		function FormTemplate($actionType)
		{
			$this->idart = bab_rp('idart');
			if ($actionType == bab_ArticleTreeView::SUBMIT_ARTICLES) {
				$this->t_no_topic = bab_translate('No topic');
				$this->next_idx = bab_toHtml('s1');
			} else {
				$this->t_no_topic = false;
				$this->next_idx = bab_toHtml('s01');
			}
			$this->rfurl = bab_toHtml(isset($GLOBALS['rfurl']) ? $GLOBALS['rfurl'] : '');

			$this->bcontent = false;
			$this->title = bab_pp('title', '');
			$this->headtext = bab_pp('headtext', '');
			$this->bodytext = bab_pp('bodytext', '');
			$this->lang = bab_pp('lang', '');
		}
	};

	$template = new FormTemplate($actionType);

	$html = bab_printTemplate($template, 'artedit.html', 'topictreeform');

	$topicTree = new bab_ArticleTreeView('article_topics_tree' . $actionType);
	$topicTree->setAttributes(bab_ArticleTreeView::SHOW_TOPICS
							| bab_ArticleTreeView::SELECTABLE_TOPICS
							| bab_ArticleTreeView::HIDE_EMPTY_TOPICS_AND_CATEGORIES
							| bab_ArticleTreeView::SHOW_TOOLBAR
							| bab_ArticleTreeView::MEMORIZE_OPEN_NODES
							);
	$topicTree->setAction($actionType);
	$topicTree->setLink('javascript:selectTopic(%s);');
	$topicTree->highlightElement('topic' . bab_ArticleTreeView::ID_SEPARATOR . $selectedTopicId);
	$topicTree->order();
	$topicTree->sort();

	$html .= $topicTree->printTemplate();

	return $html; 
}




function showChoiceArticleModify($topicid)
{
	global $babBodyPopup;
	class temp
		{
		var $res;
		var $count;
		var $topicname;
		var $topicpath;
		var $description;
		var $idtopic;
		var $topicchecked;
		var $idtopicsel;
		var $articleid;
		var $title; 
		var $headtext;
		var $bodytext;
		var $lang;
		var $bmodify;
		var $modifauthor;
		var $altbg = true;
		var $nbartmodify = 0;

		function temp($topicid)
			{
			global $babBodyPopup, $babBody, $babDB, $topicid, $articleid, $rfurl;
			$this->count = 0;
			$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topicid)."'");

			
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $topicid) || ($arr['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL,$topicid)) )
					{
					$req = "select at.id, at.title, adt.id_author, adt.id as id_draft from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where at.id_topic='".$babDB->db_escape_string($topicid)."' and at.archive='N' order by at.ordering asc";
					}
				elseif( $arr['allow_update'] && bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topicid))
					{
					$req = "select at.id, at.title, adt.id_author, adt.id as id_draft from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where at.id_topic='".$babDB->db_escape_string($topicid)."' and at.archive='N' and at.id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' order by at.ordering asc";
					}
				else
					{
					$req = '';
					}

				if( $req != '' )
					{
					$this->res = $babDB->db_query($req);
					$this->count = $babDB->db_num_rows($this->res);
					$this->rfurl = bab_toHtml($rfurl);
					$this->topicid = bab_toHtml($topicid);
					$babBodyPopup->title = bab_translate("Choose the article");
					$this->steptitle = viewCategoriesHierarchy_txt($topicid);
					$this->nexttxt = bab_translate("Next");
					$this->canceltxt = bab_translate("Cancel");
					$this->previoustxt = bab_translate("Previous");
					}
				}
			
			if( $req == '' )
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}

		function getnextarticle()
			{
			global $babDB, $babBody;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->articleid = bab_toHtml($arr['id']);
				$this->articletitle = bab_toHtml($arr['title']);
				if( $i == 0 )
					{
					$this->articlechecked = 'checked';
					}
				else
					{
					$this->articlechecked = '';
					}
				if( !isset($arr['id_author']) || empty($arr['id_author']))
					{
					$this->modifybytxt = '';
					$this->modifauthor = '';
					$this->bmodify = true;
					$this->nbartmodify++;
					}
				else
					{
					$this->modifybytxt = bab_translate("In modification by");
					$this->modifauthor = bab_toHtml(bab_getUserName($arr['id_author']));
					$this->bmodify = false;
					if( $arr['id_author'] == $GLOBALS['BAB_SESS_USERID'] )
						{
						$this->editdrafttxt = bab_translate("Edit");
						$this->editdrafturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=s1&idart=".$arr['id_draft']."&rfurl=".urlencode($this->rfurl));
						$this->bauthor = true;
						}
					else
						{
						$this->bauthor = false;
						}
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		}
	$temp = new temp($topicid);
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "modarticlechoicestep"));
}

function showEditArticle()
{
	global $babBodyPopup;
	class temp
		{
		var $topicname;
		var $topicpath;

		function temp()
			{
			global $babBodyPopup, $babBody, $babDB, $rfurl;

			$idart = bab_rp('idart', 0);
			$topicid = bab_rp('topicid', 0);
			$articleid = bab_rp('articleid', 0);
			$this->rfurl = bab_toHtml($rfurl);
			$this->access = false;
			$this->bprev = false;
			$this->warnmessage = '';
			if( isset($_POST['title'])|| isset($_POST['lang']) )
				{
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

				$editorhead = new bab_contentEditor('bab_article_head');
				$headtext = $editorhead->getContent();
				
				
				$editorbody = new bab_contentEditor('bab_article_body');
				$bodytext = $editorbody->getContent();
				
				$this->content = bab_editArticle(bab_pp('title'), $headtext, $bodytext, bab_pp('lang'), '');
				}
			else
				{
				$this->content = '';
				}

			if( $topicid != 0 && $idart != 0 )
				{
				list($drafidtopic) = $babDB->db_fetch_array($babDB->db_query("select id_topic from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'"));
				if( $topicid != $drafidtopic )
					{
					$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_topic='".$babDB->db_escape_string($topicid)."', id_article='0', restriction='', notify_members='N', hpage_public='N', hpage_private='N', date_submission='0000-00-00 00:00:00', date_publication='0000-00-00 00:00:00', date_archiving='0000-00-00 00:00:00'  where id='".$babDB->db_escape_string($idart)."'");
					$articleid = 0;
					}
				}
			
			if( $this->content == '' && ($idart != 0 || $topicid != 0 || $articleid != 0) )
				{
				if( $idart != 0 )
					{
					$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
					if( $res && $babDB->db_num_rows($res) > 0 )
						{
						$this->access = true;
						$arr = $babDB->db_fetch_array($res);
						$topicid = $arr['id_topic'];
						$articleid = $arr['id_article'];
						$this->content = bab_editArticle($arr['title'], $arr['head'], $arr['body'], $arr['lang'], "");
						}
					}
				elseif( $articleid != 0 )
					{
					$res = $babDB->db_query("select at.*, tt.allow_update, tt.allow_manupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id  where at.id='".$babDB->db_escape_string($articleid)."'");
					if( $res && $babDB->db_num_rows($res) == 1 )
						{
						$arr = $babDB->db_fetch_array($res);
						if( ($arr['allow_update'] != '0' && $arr['id_author'] == $GLOBALS['BAB_SESS_USERID'] ) || bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic'])  || ($arr['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id_topic'])))
							{
							$topicid = $arr['id_topic'];
							$this->access = true;
							}
						if( empty($this->content))
							{
							$this->content = bab_editArticle($arr['title'], $arr['head'], $arr['body'], $arr['lang'], "");
							}
						}
					}
				elseif( $topicid != 0 )
					{
					if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topicid))
						{
						$res = $babDB->db_query("select tt.article_tmpl,tt.lang from ".BAB_TOPICS_TBL." tt  where id='".$babDB->db_escape_string($topicid)."'");
						if( $res && $babDB->db_num_rows($res) == 1 )
							{
							$arr = $babDB->db_fetch_array($res);
							$this->access = true;
							if( empty($this->content))
								{
								$this->content = bab_editArticle('', '', '', $arr['lang'], $arr['article_tmpl']);
								}
							}
						}
					}
				}
			else
				{
				if( count(bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL)) > 0 )
					{
					if( empty($this->content))
						{
						$this->content = bab_editArticle('', '', '', $GLOBALS['babLanguage'], '');
						}
					$this->access = true;
					}
				}

			if( $this->access )
				{
				$this->submittxt = bab_translate("Finish");
				$this->previoustxt = bab_translate("Previous");
				$this->nexttxt = bab_translate("Next");
				$this->savetxt = bab_translate("Save and close");
				$this->canceltxt = bab_translate("Cancel");
				$this->confirmsubmit = bab_translate("Are you sure you want to submit this article?");
				$this->confirmcancel = bab_translate("Are you sure you want to remove this draft?");
				$this->idart = bab_toHtml($idart);
				$this->idtopic = bab_toHtml($topicid);
				$this->idarticle = bab_toHtml($articleid);
				if( $articleid )
					{
					$this->bprev = false;
					}
				else
					{
					$this->bprev = true;
					}
				if( $topicid != 0 )
					{
					$this->bsubmit = true;
					$this->steptitle = viewCategoriesHierarchy_txt($topicid);
					}
				else
					{
					$this->bsubmit = false;
					$this->steptitle = bab_translate("No topic");
					}

				$this->bupprobchoice = false;

				if( $articleid != 0 || $topicid != 0 )
					{
					if( $articleid != 0 )
						{
						$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate, adt.approbation from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where at.id='".$babDB->db_escape_string($articleid)."'");
						$arr = $babDB->db_fetch_array($res);
						if( $arr['saupdate'] != 0 && ( $arr['allow_update'] == '2' && $arr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $arr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id_topic'])))
							{
							$this->bupprobchoice = true;
							$this->yesapprobtxt = bab_translate("With approbation");
							$this->noapprobtxt = bab_translate("Without approbation");
							switch( $arr['approbation'] )
								{
								case 1:
									$this->yesapprobsel = "selected";
									$this->noapprobsel = "";
									break;
								case 2: 
									$this->yesapprobsel = "";
									$this->noapprobsel = "selected";
									break;
								default: 
									$this->yesapprobsel = "";
									$this->noapprobsel = "";
									break;
								}
							}
						}
					else
						{
						$res = $babDB->db_query("select tt.idsaart as saupdate from ".BAB_TOPICS_TBL." tt where tt.id='".$babDB->db_escape_string($topicid)."'");
						$arr = $babDB->db_fetch_array($res);
						}

					if( $arr['saupdate'] != 0 )
						{
						$this->warnmessage = bab_translate("Note: Articles are moderate and consequently your article will not be visible immediately");
						}
					else
						{
						$this->warnmessage = "";
						}
					}
				}
			else
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}
		}

	$temp = new temp();
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "editarticlestep"));
}

function showPreviewArticle($idart)
{
	global $babBodyPopup;
	class temp
		{
		function temp($idart)
			{
			global $babBodyPopup, $babBody, $babDB, $BAB_SESS_USERID, $rfurl;
			$babBodyPopup->title = bab_translate("Preview article");
			$this->rfurl = bab_toHtml($rfurl);
			$this->access = false;
			$res = $babDB->db_query("select id_topic, id_article, title, head, approbation from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->access = true;
				}

			if( $this->access )
				{
				$this->submittxt = bab_translate("Finish");
				$this->previoustxt = bab_translate("Previous");
				$this->nexttxt = bab_translate("Next");
				$this->savetxt = bab_translate("Save and close");
				$this->canceltxt = bab_translate("Cancel");
				$this->confirmsubmit = bab_translate("Are you sure you want to submit this article?");
				$this->confirmcancel = bab_translate("Are you sure you want to remove this draft?");
				$this->idart = bab_toHtml($idart);
				if( $arr['id_topic'] != 0 )
					{
					$this->steptitle = viewCategoriesHierarchy_txt($arr['id_topic']);
					}
				else
					{
					$this->steptitle = bab_translate("No topic");
					}

				if( $arr['id_topic'] != 0 && !empty($arr['title']) && !empty($arr['head']))
					{
					$this->bsubmit = true;
					}
				else
					{
					$this->bsubmit = false;
					}
				
				if( $arr['id_article'] != 0 )
					{
					$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id where at.id='".$babDB->db_escape_string($arr['id_article'])."'");
					$rr = $babDB->db_fetch_array($res);
					if( $rr['saupdate'] != 0 && ( $rr['allow_update'] == '2' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $rr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'])))
						{
						$this->bupprobchoice = true;
						$this->yesapprobtxt = bab_translate("With approbation");
						$this->noapprobtxt = bab_translate("Without approbation");
						switch( $arr['approbation'] )
							{
							case 1:
								$this->yesapprobsel = "selected";
								$this->noapprobsel = "";
								break;
							case 2: 
								$this->yesapprobsel = "";
								$this->noapprobsel = "selected";
								break;
							default: 
								$this->yesapprobsel = "";
								$this->noapprobsel = "";
								break;
							}
						}
					}
				else
					{
					$this->bupprobchoice = false;
					}
				$this->content = bab_previewArticleDraft($idart, 0);
				}
			else
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}
		}
	$temp = new temp($idart);
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "previewarticlestep"));
}


function showSetArticleProperties($idart)
	{
	global $babBodyPopup;
	class temp
		{

		var $altbg = true;

		var $bHaveWarningMessage	= false;
		
		var $bUploadPathValid		= false;
		var $bImageUploadPossible	= false;
		var $bImageUploadEnable		= false;
		var $bHaveAssociatedImage	= false;
		var $bDisplayDelImgChk		= false;

		var $iMaxImgFileSize		= 0;
		var $sImageTitle			= '';
		var $sSelectImageCaption	= '';
		var $sDeleteImageCaption	= '';
		var $sImagePreviewCaption	= '';
		var $sDisabledUploadReason	= '';
		var $sImageUrl				= '#';
		var $sAltImagePreview		= '';
		var $sImgName				= '';
		var $sImageSubmitCaption	= '';
		var $sDeleteImageChecked	= '';
		var $iMaxFileSize			= 0;
		var $sMaxImgFileSizeMsg		= '';
		
		
		function temp($idart)
			{
			global $babBodyPopup, $babBody, $babDB, $BAB_SESS_USERID, $topicid, $rfurl;
			
			$this->iMaxFileSize = max($GLOBALS['babMaxImgFileSize'], $GLOBALS['babMaxFileSize']);
/*			
echo 
	'babMaxImgFileSize ==> ' . $GLOBALS['babMaxImgFileSize'] . 
	' babMaxFileSize ==> ' . $GLOBALS['babMaxFileSize'] . 
	'<br/>';
//*/			
			$this->warnfilemessage	= '';
			$this->access			= false;
			$this->rfurl			= $rfurl;

			$req = "select * from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and id='".$babDB->db_escape_string($idart)."'";
			$res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($res);
			if( $this->count > 0 )
				{
				$this->access = true;
				$this->idart = bab_toHtml($idart);
				$arr = $babDB->db_fetch_array($res);
				$this->submittxt = bab_translate("Finish");
				$this->previoustxt = bab_translate("Previous");
				$this->savetxt = bab_translate("Save and close");
				$this->canceltxt = bab_translate("Cancel");
				$this->topictxt = bab_translate("Topic");
				$this->titletxt = bab_translate("Title");
				$this->confirmsubmit = bab_translate("Are you sure you want to submit this article?");
				$this->confirmcancel = bab_translate("Are you sure you want to remove this draft?");

				$this->t_file = bab_translate("File");
				$this->t_description = bab_translate("Description");
				$this->t_dragmessage = bab_translate("To order files, drag and drop here");
				$this->t_dragmessage_user = bab_translate("To order files, drag and drop and don't forget to save");
				$this->t_deletemessage_user = bab_translate("To delete files use checkboxes");
				$this->t_index_status = bab_translate("Indexation");

				if( $arr['id_topic'] != 0 && ( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $arr['id_topic'])|| bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic'])))
					{
					$this->steptitle = viewCategoriesHierarchy_txt($arr['id_topic']);
					}
				elseif( $arr['id_topic'] != 0 )
					{
					//Try to verify if current user can update article as manager or author 
					$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, adt.id_article from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where adt.id='".$babDB->db_escape_string($idart)."'");
					$rr = $babDB->db_fetch_array($res);				
					if(( $rr['allow_update'] != '0' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID'])      
					|| ( $rr['allow_manupdate'] != '0' && bab_isAccessValidByUser(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'], $GLOBALS['BAB_SESS_USERID']))) 
						{
						$this->steptitle = viewCategoriesHierarchy_txt($arr['id_topic']);
						}
					else
						{
						$arr['id_topic'] = 0;
						}
					}
					
				if($arr['id_topic'] == 0 ) 
					{
					$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_topic='0' where id='".$babDB->db_escape_string($idart)."'");
					$this->steptitle = bab_translate("No topic");
					}

				if( $arr['id_topic'] != 0 && !empty($arr['title']) && !empty($arr['head']))
					{
					$this->bsubmit = true;
					}
				else
					{
					$this->bsubmit = false;
					}

				if( $arr['id_article'] != 0 )
					{
					$this->bshowtopics = false;
					$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id where at.id='".$babDB->db_escape_string($arr['id_article'])."'");
					$rr = $babDB->db_fetch_array($res);
					if( $rr['saupdate'] != 0 && ( $rr['allow_update'] == '2' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $rr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'])))
						{
						$this->bupprobchoice = true;
						$this->yesapprobtxt = bab_translate("With approbation");
						$this->noapprobtxt = bab_translate("Without approbation");
						switch( $arr['approbation'] )
							{
							case 1:
								$this->yesapprobsel = "selected";
								$this->noapprobsel = "";
								break;
							case 2: 
								$this->yesapprobsel = "";
								$this->noapprobsel = "selected";
								break;
							default: 
								$this->yesapprobsel = "";
								$this->noapprobsel = "";
								break;
							}
						}
					}
				else
					{
					$this->bupprobchoice = false;
					$this->bshowtopics = true;
					}

				$this->draftname = bab_toHtml($arr['title']);

				if( count(bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL)) > 0 )
					{
					$this->restopics = $babDB->db_query("select tt.id, tt.category, tt.restrict_access, tct.title, tt.notify from ".BAB_TOPICS_TBL." tt LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." tct on tct.id=tt.id_cat where tt.id IN(".$babDB->quote(array_keys(bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL))).")");
					$this->counttopics = $babDB->db_num_rows($this->restopics);
					}
				else
					{
					$this->counttopics = 0;
					}

				$this->allowpubdates  = false;
				$this->notifymembers = false;
				$this->restrictaccess = false;
				$this->allowhpages = false;
				$this->allowattachments  = false;

				$this->elapstime = 5;
				$this->ampm = $babBody->ampm;

				$this->datesubtitle = bab_translate("Date of submission");
				$this->datesuburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateSub&ymin=0&ymax=2";
				$this->datesubtxt = bab_translate("Submission date");
				$this->invaliddate = bab_toHtml(bab_translate("ERROR: End date must be older"),BAB_HTML_JS);

				if( isset($_POST['cdates'])) 
					{
					$arr['date_submission'] = sprintf("%04d-%02d-%02d %s:00", date("Y") + $_POST['yearsub'] - 1, $_POST['monthsub'], $_POST['daysub'], $_POST['timesub']);
					}
				
				$this->cdateecheck = '';
				if( $arr['date_submission'] != '0000-00-00 00:00:00' )
					{
					$this->cdatescheck = 'checked';
					$rr = explode(" ", $arr['date_submission']);
					$rr0 = explode("-", $rr[0]);
					$rr1 = explode(":", $rr[1]);
					$this->yearsub = $rr0[0];
					$this->monthsub = $rr0[1];
					$this->daysub = $rr0[2];
					$this->timesub = $rr1[0].":".$rr1[1];
					}
				else
					{
					$this->cdatescheck = '';
					$this->yearsub = date("Y");
					$this->monthsub = date("n");
					$this->daysub = date("j");
					$this->timesub = "00:00";
					}

				$this->daysel = $this->daysub;
				$this->monthsel = $this->monthsub;
				$this->yearsel = $this->yearsub - date("Y") + 1;
				$this->timesel = $this->timesub;

				$this->drafttopic = $topicid == '' ? $arr['id_topic']: $topicid;
				/* Traiter le cas de modification d'article */
				$topsub = bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL);
				$topmod = bab_getUserIdObjects(BAB_TOPICSMOD_GROUPS_TBL);
				if( (count($topsub) == 0 || !isset($topsub[$this->drafttopic])) && (count($topmod) == 0 || !isset($topmod[$this->drafttopic])))
					{
					$this->drafttopic = 0;
					}

				$this->topicpath = '';
				
				{//Image
					$this->sDeleteImageChecked	= (bab_rp('deleteImageChk', 0) == 0) ? '' : 'checked="checked"';
					$this->sImgName				= bab_rp('sImgName', '');
					$this->sImageUrl			= $GLOBALS['babUrlScript'] . '?tg=artedit&idx=getImage&iWidth=120&iHeight=90' . 
						'&iIdDraft=' . $idart . '&sImage=';
									
					//Si on ne vient pas d'un post alors récupérer l'image
					if(!array_key_exists('sImgName', $_POST))
					{
						$aImageInfo	= bab_getImageDraftArticle($idart);
						if(false !== $aImageInfo)
						{
							$this->sImgName				= $aImageInfo['name'];
							$this->sImageUrl			.= bab_toHtml($this->sImgName);
							$this->warnfilemessage 		= bab_translate("Warning! If you change topic, you can lost associated documents");
							$this->bHaveAssociatedImage = true;
						}
					}
					else if('' != $this->sImgName)
					{
						$this->sImageUrl 			.= bab_toHtml($this->sImgName);
						$this->bHaveAssociatedImage = true;
						$this->warnfilemessage 		= bab_translate("Warning! If you change topic, you can lost associated documents");
					}
					else
					{
						$this->sImageUrl = '#';
					}
				}
				
				
				if( $this->drafttopic != 0 )
					{
					$this->topicpath = viewCategoriesHierarchy_txt($this->drafttopic);
					$arrtop = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($this->drafttopic)."'"));

					$this->bUploadPathValid		= is_dir($GLOBALS['babUploadPath']);
					$this->bImageUploadEnable	= ('Y' === (string) $arrtop['allow_addImg']);
					$this->sMaxImgFileSizeMsg	= '';
					
					
					if($this->bImageUploadEnable)
					{
						$this->sMaxImgFileSizeMsg = '('.bab_translate("File size must not exceed")." ".(int) ($GLOBALS['babMaxImgFileSize'] / 1024). " ". bab_translate("Ko").')';
					}
					
					$this->bImageUploadPossible	= (0 < $GLOBALS['babMaxImgFileSize'] && $this->bUploadPathValid);
					
					$this->sImageTitle			= bab_translate('Associated picture');
					$this->sSelectImageCaption	= bab_translate('Select a picture');
					$this->sDeleteImageCaption	= bab_translate('Remove image');
					$this->sImagePreviewCaption = bab_translate('Preview image');
					$this->sAltImagePreview		= bab_translate("Previlualization of the image");
					$this->sImageSubmitCaption	= bab_translate("Update");
					
					$this->processDisabledUploadReason();
					
					if( $arrtop['busetags'] == 'Y')
						{
						$this->tagsvalue = bab_pp('tagsname', '');
						$this->busetags = true;
						$this->tagstxt = bab_translate("Keywords of the thesaurus");
						$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
						$babBody->addJavascriptFile($GLOBALS['babScriptPath']."scriptaculous/scriptaculous.js");
						$babBodyPopup->addStyleSheet('ajax.css');
						if( empty($this->tagsvalue))
							{
							require_once dirname(__FILE__) . '/utilit/tagApi.php';
							$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
							$oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $idart));
							$oIterator->orderAsc('tag_name');
							foreach($oIterator as $oTag)
								{
								$this->tagsvalue .= $oTag->getName() . ', ';
								}
							}
						}

					if( $arrtop['notify'] == 'Y')
						{
						$this->notifymembers = true;
						$this->notifytitle = bab_translate("Notification");
						$this->notifmtxt = bab_translate("Notify users once the article is published ");
						if( isset($_POST['updstep3']) ) 
							{
							if( isset($_POST['notifm']) && $_POST['notifm'] == 'Y')
								{
								$arr['notify_members'] = 'Y';
								}
							else
								{
								$arr['notify_members'] = 'N';
								}
							}

						if( $arr['notify_members'] == 'Y')
							{
							$this->cnotifmcheck = 'checked';
							}
						else
							{
							$this->cnotifmcheck = '';
							}
						}

					if( $arrtop['restrict_access'] == 'Y' )
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
						$this->t_grp_error = bab_translate("The read access on the topic must be defined with a list of groups to use the group restriction");
						$this->resgrp = $babDB->db_query("select r.* from ".BAB_TOPICSVIEW_GROUPS_TBL." r,".BAB_GROUPS_TBL." g where r.id_object='".$babDB->db_escape_string($this->drafttopic)."' AND r.id_group = g.id AND g.lf>='3'");
						if( $this->resgrp )
							{
							if( isset($_POST['updstep3'])) 
								{
								if( isset($_POST['notifm']) && $_POST['notifm'] == 'Y')
									{
									$arr['notify_members'] = 'Y';
									}
								else
									{
									$arr['notify_members'] = 'N';
									}
								}

							$this->countgrp = $babDB->db_num_rows($this->resgrp);
							if( strchr($arr['restriction'], "&"))
								{
								$this->arrrest = explode('&', $arr['restriction']);
								$this->operatororysel = '';
								$this->operatorornsel = 'selected';
								}
							else if( strchr($arr['restriction'], ","))
								{
								$this->arrrest = explode(',', $arr['restriction']);
								$this->operatororysel = 'selected';
								$this->operatorornsel = '';
								}
							else
								{
								$this->arrrest = array($arr['restriction']);
								$this->operatororysel = '';
								$this->operatorornsel = '';
								}

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
						else
							{
							$this->countgrp = 0;
							$this->restrictaccess = false;
							}
						}

					if( $arrtop['allow_hpages'] == 'Y' )
						{
						$this->allowhpages = true;
						$this->hpagestitle = bab_translate("Home pages");
						$this->hpage0txt = bab_translate("Add to unregistered users home page");
						$this->hpage1txt = bab_translate("Add to registered users home page");
						if( isset($_POST['updstep3'])) 
							{
							if( isset($_POST['hpage0']) && $_POST['hpage0'] == 'Y')
								{
								$arr['hpage_private'] = 'Y';
								}
							else
								{
								$arr['hpage_private'] = 'N';
								}
							if( isset($_POST['hpage1']) && $_POST['hpage0'] == 'Y')
								{
								$arr['hpage_public'] = 'Y';
								}
							else
								{
								$arr['hpage_public'] = 'N';
								}
							}

						if( $arr['hpage_public'] == 'Y' )
							{
							$this->chpage0check = "checked";
							}
						else
							{
							$this->chpage0check = "";
							}

						if( $arr['hpage_private'] == 'Y' )
							{
							$this->chpage1check = "checked";
							}
						else
							{
							$this->chpage1check = "";
							}
						}

					if( $arrtop['allow_pubdates'] == 'Y' )
						{
						$this->allowpubdates  = true;
						$this->datepubtitle = bab_translate("Dates of publication and archiving");
						$this->datebeginurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=2";
						$this->datebegintxt = bab_translate("Publication date");
						$this->dateendurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=2";
						$this->dateendtxt = bab_translate("Archiving date");

						if( isset($_POST['cdateb'])) 
							{
							$arr['date_publication'] = sprintf("%04d-%02d-%02d %s:00", date("Y") + $_POST['yearbegin'] - 1, $_POST['monthbegin'], $_POST['daybegin'], $_POST['timebegin']);
							}
						if( isset($_POST['cdatee'])) 
							{ 
							$arr['date_archiving'] = sprintf("%04d-%02d-%02d %s:00", date("Y") + $_POST['yearend'] - 1, $_POST['monthend'], $_POST['dayend'], $_POST['timeend']);
							}
						if( $arr['date_publication'] != '0000-00-00 00:00:00' )
							{
							$this->cdatebcheck = 'checked';
							$rr = explode(" ", $arr['date_publication']);
							$rr0 = explode("-", $rr[0]);
							$rr1 = explode(":", $rr[1]);
							$this->yearbegin = $rr0[0];
							$this->monthbegin = $rr0[1];
							$this->daybegin = $rr0[2];
							$this->timebegin = $rr1[0].":".$rr1[1];
							}
						else
							{
							$this->cdatebcheck = '';
							$this->yearbegin = date("Y");
							$this->monthbegin = date("n");
							$this->daybegin = date("j");
							$this->timebegin = "00:00";
							}
						if( $arr['date_archiving'] != '0000-00-00 00:00:00' )
							{
							$this->cdateecheck = 'checked';
							$rr = explode(" ", $arr['date_archiving']);
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
						}

					if( $arrtop['allow_attachments'] == 'Y' )
						{
						$this->allowattachments  = true;
						$this->addtxt = bab_translate("Update");
						$this->filetxt = bab_translate("File");
						$this->desctxt = bab_translate("Description");
						$this->filetitle = bab_translate("Associated documents");
						$this->deletetxt = bab_translate("Delete");
						$this->t_add_field = bab_translate("Attach another file");
						$this->t_remove_field = bab_translate("Remove");
						$this->resfiles = $babDB->db_query("select id, name, description from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."' order by ordering asc");
						$this->maximagessize = $babBody->babsite['imgsize'];
						
						if( $babBody->babsite['maxfilesize'] != 0 )
							{
							$this->maxsizetxt = '('.bab_translate("File size must not exceed")." ".$babBody->babsite['maxfilesize']. " ". bab_translate("Mb").')';
							}
						else
							{
							$this->maxsizetxt = '';
							}
						$this->countfiles = $babDB->db_num_rows($this->resfiles);
						if( $this->countfiles > 0 )
							{
							$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
							$babBody->addJavascriptFile($GLOBALS['babScriptPath']."scriptaculous/scriptaculous.js");
							$this->warnfilemessage = bab_translate("Warning! If you change topic, you can lost associated documents");
							}
						}
					}
					
					$this->bHaveWarningMessage = ('' != $this->warnfilemessage);
				}
			else
				{
				$message = bab_translate("Access denied");
				}

			if(!empty($message))
				{
				$this->msgerror = $message;
				$this->message = bab_printTemplate($this,"warning.html", "texterror");
				}
			else
				{
				$this->message = '';
				}

			}

		function processDisabledUploadReason()
		{
			$this->sDisabledUploadReason = bab_translate("Loading image is not active because");
			$this->sDisabledUploadReason .= '<UL>';
				
			if('' == $GLOBALS['babUploadPath'])
			{
				$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not set");
			}
			else if(!is_dir($GLOBALS['babUploadPath']))
			{
				$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not a dir");
			}
			
			if(0 == $GLOBALS['babMaxImgFileSize'])
			{
				$this->sDisabledUploadReason .= '<LI>'. bab_translate("The maximum size for a defined image is zero byte");
			}
			$this->sDisabledUploadReason .= '</UL>';
		}
			
		function getnexttopic()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counttopics)
				{
				$arr = $babDB->db_fetch_array($this->restopics);
				$this->topicname = bab_toHtml($arr['category']);
				$this->categoryname = bab_toHtml($arr['title']);
				$this->idtopic = bab_toHtml($arr['id']);
				if( $this->drafttopic == $arr['id'] )
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
				$this->grpname = bab_getGroupName($arr['id_group'], false);
				if( in_array($this->grpid, $this->arrrest))
					$this->grpcheck = 'checked';
				else
					$this->grpcheck = '';
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnextfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countfiles)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->resfiles);
				$this->urlfile = $GLOBALS['babUrlScript']."?tg=artedit&idx=getf&idart=".$this->idart."&idf=".$arr['id'];
				$this->deleteurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=s3&updstep3=delf&idart=".$this->idart."&idf=".$arr['id'];
				$this->name = bab_toHtml($arr['name']);
				$this->docdesc = bab_toHtml($arr['description']);
				$this->idfile = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;

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
					$this->selected = "";
				
				$i++;
				return true;
				}
			else
				{
				if( $p == 0 && $this->allowpubdates )
					{
					$this->daysel = $this->daybegin;
					$p++;
					}
				elseif( $this->allowpubdates )
					{
					$this->daysel = $this->dayend;
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
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				if( $p == 0  && $this->allowpubdates )
					{
					$this->monthsel = $this->monthbegin;
					$p++;
					}
				elseif( $this->allowpubdates )
					{
					$this->monthsel = $this->monthend;
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
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				if( $p == 0  && $this->allowpubdates )
					{
					$this->yearsel = $this->yearbegin - date("Y") + 1;
					$p++;
					}
				elseif( $this->allowpubdates )
					{
					$this->yearsel = $this->yearend - date("Y") + 1;
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
					$this->time = bab_toAmPm($this->timeval);
				else
					$this->time = $this->timeval;
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
				if( $p == 0  && $this->allowpubdates )
					{
					$this->timesel = $this->timebegin;
					$p++;
					}
				elseif( $this->allowpubdates )
					{
					$this->timesel = $this->timeend;
					}
				$i = 0;
				return false;
				}

			}
		}
	$babBodyPopup->addStyleSheet('publication.css');
	
	$temp = new temp($idart);
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "propertiesarticlestep"));
	}



function getDocumentArticleDraft( $idart, $idf )
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$access = false;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id='".$babDB->db_escape_string($idf)."' and id_draft='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	if( !$access )
		{
		echo bab_translate("Access denied");
		return;
		}

	$arr = $babDB->db_fetch_array($res);
	$file = stripslashes($arr['name']);

	$fullpath = bab_getUploadDraftsPath();
	

	$fullpath .= $arr['id_draft'].",".$file;
	$fsize = filesize($fullpath);

	$mime = bab_getFileMimeType($file);

	if( mb_strtolower(bab_browserAgent()) == "msie")
		header('Cache-Control: public');
	$inl = bab_getFileContentDisposition() == 1? 1: '';
	if( $inl == '1' )
		{
		header("Content-Disposition: inline; filename=\"$file\""."\n");
		}
	else
		{
		header("Content-Disposition: attachment; filename=\"$file\""."\n");
		}
	header("Content-Type: $mime"."\n");
	header("Content-Length: ". $fsize."\n");
	header("Content-transfert-encoding: binary"."\n");
	$fp=fopen($fullpath,"rb");
	print fread($fp,$fsize);
	fclose($fp);
	}

function delDocumentArticleDraft( $idart, $idf )
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$access = false;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	if( !$access )
		{
		echo bab_translate("Access denied");
		return false;
		}

	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id='".$babDB->db_escape_string($idf)."' and id_draft='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$fullpath = bab_getUploadDraftsPath();
		$fullpath .= $arr['id_draft'].",".$arr['name'];
		unlink($fullpath);
		$babDB->db_query("delete from ".BAB_ART_DRAFTS_FILES_TBL." where id='".$babDB->db_escape_string($idf)."'");
		}
	return true;
	}


function deleteDraft($idart)
	{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select result, id_article from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['result'] != BAB_ART_STATUS_WAIT )
			{
			if( $arr['id_article'] != 0 )
				{
				$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$babDB->db_escape_string($arr['id_article'])."', '".$babDB->db_escape_string($BAB_SESS_USERID)."', now(), 'unlock')");		
				}
			bab_deleteArticleDraft($idart);
			}
		}
	}

function emptyTrash()
	{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where trash='Y' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		deleteDraft($arr['id']);
		}
	}

function moveArticleDraftToTrash($idart)
	{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id, result, id_article from ".BAB_ART_DRAFTS_TBL." where trash='N' and id='".$idart."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['result'] != BAB_ART_STATUS_WAIT )
			{
			if( $arr['id_article'] != 0 )
				{
				$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$arr['id_article']."', '".$babDB->db_escape_string($BAB_SESS_USERID)."', now(), 'unlock')");		
				}
			$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_article='0', trash='Y' where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
			}
		}
	}

function restoreArticleDraft($idart)
	{
	global $babDB;
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set trash='N' where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	list($nbtrash) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and trash !='N'"));
	if( $nbtrash > 0 )
		{
		return false;
		}
	else
		{
		return true;
		}
	}

function restoreRefusedArticleDraft($idart)
	{
	global $babDB;
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set trash='N', result='', date_submission='0000-00-00 :00:00:00' where id='".$idart."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	list($nbsub) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and result !='".BAB_ART_STATUS_DRAFT."'"));
	if( $nbsub > 0 )
		{
		return false;
		}
	else
		{
		return true;
		}
	}


function editArticleDraft($idart, $title, $lang, $message)
	{
	global $babBodyPopup;
	class temp
		{
		var $content;
		var $idart;

		function temp($idart, $title, $lang, $message)
			{
			global $babDB, $babBodyPopup, $BAB_SESS_USERID;
			$this->idart = bab_toHtml($idart);
			if(!empty($message))
				{
				$babBodyPopup->msgerror = $message;
				}
			else
				{
				$babBodyPopup->message = '';
				}
			$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$this->updatetxt = bab_translate("Update");
				if( !empty($title) || !empty($lang) )
					{
					include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

					$editorhead = new bab_contentEditor('bab_article_head');
					$headtext = $editorhead->getContent();
					
					
					$editorbody = new bab_contentEditor('bab_article_body');
					$bodytext = $editorbody->getContent();
					
					$this->content = bab_editArticle($title, $headtext, $bodytext, $lang, '');
					}
				else
					{
					$arr = $babDB->db_fetch_array($res);
					$this->content = bab_editArticle($arr['title'], $arr['head'], $arr['body'], '', '');
					}
				}
			else
				{
				$this->content = bab_translate("Access denied");
				}
			}

		}

	$temp = new temp($idart, $title, $lang, $message);
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "editdraft"));
	}

function previewArticleDraft($idart)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		class temp
			{
			var $content;
			
			
			function temp($idart)
				{
				$this->content	= bab_previewArticleDraft($idart, 0);
				}
			}

		$temp = new temp($idart);
		$babBody->babPopup( bab_printTemplate($temp, "artedit.html", "previewarticle"));
		}
	else
		{
		$babBody->babPopup( bab_translate("Access denied"));
		}
	}


function updateArticleDraft($idart, $title,  $lang, $approbid, &$message)
{
	global $babDB, $BAB_SESS_USERID, $babBody ;
	include_once $GLOBALS['babInstallPath']."utilit/imgincl.php";
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	
	$headeditor = new bab_contentEditor('bab_article_head');
	$headtext = $headeditor->getContent();
	
	$bodyeditor = new bab_contentEditor('bab_article_body');
	$bodytext = $bodyeditor->getContent();

	$title = trim($title);
	$bodytext = trim($bodytext);
	$headtext = trim($headtext);

	if( empty($title))
		{
		$message = bab_translate("ERROR: You must provide a title");
		return false;
		}

	if( empty($headtext))
		{
		$message = bab_translate("ERROR: You must provide a head for your article");
		return false;
		}

	if($lang == '') { $lang = $GLOBALS['babLanguage']; }

	if( !bab_compare(mb_strtolower($bodytext), mb_strtolower("<P>&nbsp;</P>")) || 
	    !bab_compare(mb_strtolower($bodytext), mb_strtolower("<P />")))
		{
		$bodytext = "";
		}


	$ar = array();
	$headtext = imagesReplace($headtext, $idart."_draft_", $ar);
	$bodytext = imagesReplace($bodytext, $idart."_draft_", $ar);

	$babDB->db_query("
	
	UPDATE ".BAB_ART_DRAFTS_TBL." 
	SET 
		title='".$babDB->db_escape_string($title)."', 
		head='".$babDB->db_escape_string($headtext)."', 
		body='".$babDB->db_escape_string($bodytext)."', 
		date_modification=now(), 
		lang='".$babDB->db_escape_string($lang)."', 
		approbation='".$babDB->db_escape_string($approbid)."' 
	WHERE 
		id='".$babDB->db_escape_string($idart)."'
	");
	
	return true;
}


function updateDocumentsArticleDraft($idart, &$message)
{
	global $babDB, $BAB_SESS_USERID, $babMaxFileSize;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	$k = 0;
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";

		$okfiles = 0;
		$dfiles = bab_pp('dfiles', array());
		if( count($dfiles))
			{
			for( $i = 0; $i < count($dfiles); $i++ )
				{
				delDocumentArticleDraft($idart, $dfiles[$i]);
				$okfiles++;
				}
			}

		$errfiles = array();
		foreach ($_FILES as $file) 
			{
			if( empty($file['name']) || $file['name'] == 'none')
				{
				$k++;
				continue;
				}

			if( $file['size'] > $GLOBALS['babMaxFileSize'])
				{
				$errfiles[] = array('error'=> bab_translate("The file was larger than the maximum allowed size") ." :". $GLOBALS['babMaxFileSize'], 'file'=>$file['name']);
				$k++;
				continue;
				}

			$totalsize = getDirSize($GLOBALS['babUploadPath']);

			if( $file['size'] + $totalsize > $GLOBALS['babMaxTotalSize'])
				{
				$errfiles[] = array('error'=> bab_translate("There is not enough free space"), 'file'=>$file['name']);
				$k++;
				continue;
				}

			$filename = trim($file['name']);

			if( isset($GLOBALS['babFileNameTranslation']))
				{
				$filename = strtr($filename, $GLOBALS['babFileNameTranslation']);
				}

			$osfname = $idart.",".$filename;
			$path = bab_getUploadDraftsPath();
			if( $path === false )
				{
				$errfiles[] = array('error'=> bab_translate("Can't create directory"), 'file'=>$file['name']);
				$k++;
				continue;
				}
			
			if( file_exists($path.$osfname))
				{
				$errfiles[] = array('error'=> bab_translate("A file with the same name already exists"), 'file'=>$file['name']);
				$k++;
				continue;
				}

			bab_setTimeLimit(0);
			if( !move_uploaded_file($file['tmp_name'], $path.$osfname))
				{
				$errfiles[] = array('error'=> bab_translate("The file could not be uploaded"), 'file'=>$file['name']);
				$k++;
				continue;
				}

			$description = $_POST['docdesc'][$k];
			
			$res = $babDB->db_query("select max(ordering) from  ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$babDB->db_escape_string($idart)."'");
			$rr = $babDB->db_fetch_array($res);
			if( isset($rr[0]))
				{
				$ord = $rr[0] + 1;
				}
			else
				{
				$ord = 1;
				}
			
			$babDB->db_query("insert into ".BAB_ART_DRAFTS_FILES_TBL." (id_draft, name, description, ordering) values ('" .$babDB->db_escape_string($idart). "', '".$babDB->db_escape_string($filename)."','".$babDB->db_escape_string($description)."', '".$ord."')");
			$okfiles++;
			$k++;
			}

		if( count($errfiles))
			{
			for( $k=0; $k < count($errfiles); $k++)
				{
				$message .= '<br />'.$errfiles[$k]['file'].'['.$errfiles[$k]['error'].']';
				}
			return false;
			}
		
		if( !$okfiles)
			{
			$message = bab_translate("Please select a file to upload");
			return false;
			}

		}
	return false;
}


function updatePropertiesArticleDraft(&$message)
{
	global $babBody, $babDB, $BAB_SESS_USERID, $idart, $cdateb, $cdatee, $cdates, $yearbegin, $monthbegin, $daybegin, $timebegin, $yearend, $monthend, $dayend, $timeend, $yearsub, $monthsub, $daysub, $timesub, $restriction, $grpids, $operator, $hpage0, $hpage1, $notifm, $approbid;

	list($topicid) = $babDB->db_fetch_array($babDB->db_query("select id_topic from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'"));
	$topicidnew = bab_pp('topicid', 0 );
	$topicid = $topicidnew == 0 ? $topicid: $topicidnew;
	
	if( $topicid != 0 )
	{
		list($busetags) = $babDB->db_fetch_array($babDB->db_query("select busetags from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topicid)."'"));
	}
	else
	{
		$busetags = 'N';
	}

	$otags = array();
	if( $busetags == 'Y' )
	{
		$tags = bab_rp('tagsname', '');
		$tags = trim($tags);

		if( !empty($tags))
		{
			$atags = explode(',', $tags);
			for( $k = 0; $k < count($atags); $k++ )
			{
				$tag = trim($atags[$k]);
				if( !empty($tag) )
				{
					$res = $babDB->db_query("select id from ".BAB_TAGS_TBL." where tag_name='".$babDB->db_escape_string($tag)."'");
					if( $res && $babDB->db_num_rows($res))
					{
						$arr = $babDB->db_fetch_array($res);
						$otags[] = $arr['id'];

					}
					else
					{
						$message = bab_translate("Some tags doesn't exist");
						return false;
					}
				}
			}
		}

		if( empty($tags) || count($otags) == 0 )
		{
			$message = bab_translate("You must specify at least one tag");
			return false;
		}
	}


	$date_sub = "0000-00-00 00:00";
	$date_pub = "0000-00-00 00:00";
	$date_arch = "0000-00-00 00:00";
	if( isset($cdateb)) 
		{
		$date_pub = sprintf("%04d-%02d-%02d %s:00", date("Y") + $yearbegin - 1, $monthbegin, $daybegin, $timebegin);
		}
	if( isset($cdatee)) 
		{ 
		$date_arch = sprintf("%04d-%02d-%02d %s:00", date("Y") + $yearend - 1, $monthend, $dayend, $timeend);
		}
	if( isset($cdates)) 
		{
		$date_sub = sprintf("%04d-%02d-%02d %s:00", date("Y") + $yearsub - 1, $monthsub, $daysub, $timesub);
		}

	if( isset($restriction) && !empty($restriction))
		{
		if( isset($grpids) && count($grpids) > 0)
			{
			$restriction = implode($operator, $grpids);
			}
		}
	else
		{
		$restriction = '';
		}

	if( !isset($hpage0)) { $hpage0 = 'N';} 
	if( !isset($hpage1)) { $hpage1 = 'N';} 
	if( !isset($notifm)) { $notifm = 'N';} 
	if( !isset($approbid)) { $approbid = '0';} 
	
	if( !bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topicid) && !bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $topicid))
		{
		//Try to verify if current user can update article as manager or author 
		$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, adt.id_article from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where adt.id='".$babDB->db_escape_string($idart)."'");
		$rr = $babDB->db_fetch_array($res);				
		if(( $rr['allow_update'] == '0' || $rr['id_author'] != $GLOBALS['BAB_SESS_USERID'])      
		&& ( $rr['allow_manupdate'] == '0' || !bab_isAccessValidByUser(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'], $GLOBALS['BAB_SESS_USERID']))) 
			{
				$topicid= 0;
			}
		}
		
	if( $topicid != 0 )
	{
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_topic='".$babDB->db_escape_string($topicid)."', restriction='".$babDB->db_escape_string($restriction)."', notify_members='".$babDB->db_escape_string($notifm)."', hpage_public='".$babDB->db_escape_string($hpage0)."', hpage_private='".$babDB->db_escape_string($hpage1)."', date_submission='".$babDB->db_escape_string($date_sub)."', date_publication='".$babDB->db_escape_string($date_pub)."', date_archiving='".$babDB->db_escape_string($date_arch)."', approbation='".$babDB->db_escape_string($approbid)."'  where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	list($allowattach, $busetags) = $babDB->db_fetch_array($babDB->db_query("select allow_attachments, busetags from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topicid)."'"));
	if( $allowattach == 'N' )
		{
		bab_deleteDraftFiles($idart);
		}
	if( $busetags == 'N' )
		{
		require_once dirname(__FILE__) . '/utilit/tagApi.php';
		$oReferenceMgr	= bab_getInstance('bab_ReferenceMgr');
		$oReference		= bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $idart);
		$oReferenceMgr->removeByReference($oReference);
		}
	}
	else
	{
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_topic='0', restriction='', notify_members='N', hpage_public='N', hpage_private='N', date_submission='".$babDB->db_escape_string($date_pub)."', date_publication='0000-00-00 00:00:00', date_archiving='0000-00-00 00:00:00', approbation='".$babDB->db_escape_string($approbid)."' where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	bab_deleteDraftFiles($idart);
	}

	if( count($otags))
	{
		require_once dirname(__FILE__) . '/utilit/tagApi.php';
		$oTagMgr		= bab_getInstance('bab_TagMgr');
		$oReferenceMgr	= bab_getInstance('bab_ReferenceMgr');
		$oReference		= bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $idart);
		$oReferenceMgr->removeByReference($oReference);

		for( $k = 0; $k < count($otags); $k++ )
			{
			$oTag = $oTagMgr->getById($otags[$k]);
			if($oTag instanceof bab_Tag)
				{
				$oReferenceMgr->add($oTag->getName(), $oReference);
				}
			}
	}

	$sfiles = bab_rp('sfiles', '');
	if( !empty($sfiles))
	{
		$asfiles = explode(',', $sfiles );
		for( $k = 0; $k < count($asfiles); $k++ )
		{
			$babDB->db_query("update ".BAB_ART_DRAFTS_FILES_TBL." set ordering='".$k."' where id='".$babDB->db_escape_string($asfiles[$k])."'");
		}
	}

	return true;
}


function submitArticleDraft( $idart, &$message, $force=false)
{
	global $babBody, $babDB;
	$res = $babDB->db_query("select id_article,id_topic, date_submission from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['id_article'] !=  0 )
			{
			$access = false;
			$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id  where at.id='".$babDB->db_escape_string($arr['id_article'])."'");
			if( $res && $babDB->db_num_rows($res) == 1 )
				{
				$rr = $babDB->db_fetch_array($res);
				if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $rr['id_topic']) || ( $rr['allow_update'] != '0' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $rr['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'])))
					{
					$access = true;
					}
				}
				
			if( !$access )
				{
				$message = bab_translate("You don't have rights to modify this article");
				return false;
				}
			}
		else
			{
			if( $arr['id_topic'] == 0 )
				{
				$message = bab_translate("You must specify a topic");
				return false;
				}
			elseif( !bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $arr['id_topic']) )
				{
				$message = bab_translate("You don't have rights to submit articles in this topic");
				return false;
				}			
			}
		
		if( $arr['id_topic'] != 0 )
		{
		list($busetags) = $babDB->db_fetch_array($babDB->db_query("select busetags from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($arr['id_topic'])."'"));
		}
		else
		{
			$busetags = 'N';
		}

		if( $busetags == 'Y' )
			{
			require_once dirname(__FILE__) . '/utilit/tagApi.php';
			$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
			$oReferenceDraft = bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $idart);
			$oIterator = $oReferenceMgr->getTagsByReference($oReferenceDraft);
			$nbtags = $oIterator->count();

			if( !$nbtags )
				{
				$message = bab_translate("You must specify at least one tag in article properties page");
				return false;
				}
			}

		if( $arr['id_topic'] != 0 )
		{
		list($busetags) = $babDB->db_fetch_array($babDB->db_query("select busetags from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($arr['id_topic'])."'"));
		}
		else
		{
			$busetags = 'N';
		}

		if( $busetags == 'Y' )
			{
			require_once dirname(__FILE__) . '/utilit/tagApi.php';
			$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
			$oReferenceDraft = bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $idart);
			$oIterator = $oReferenceMgr->getTagsByReference($oReferenceDraft);
			$nbtags = $oIterator->count();
			
			if( !$nbtags )
				{
				$message = bab_translate("You must specify at least one tag in article properties page");
				return false;
				}
			}

		if( !$force && $arr['date_submission'] != "0000-00-00 00:00:00" && bab_mktime($arr['date_submission']) > mktime())
			{
			return true;
			}
		return bab_submitArticleDraft( $idart);
		}
	else
	{
		$message = bab_translate("Access denied");
		return false;
	}
}

function savePreviewDraft($idart, $approbid)
{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$BAB_SESS_USERID."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set approbation='".$babDB->db_escape_string($approbid)."' where id='".$babDB->db_escape_string($idart)."'");
		}
}

function artedit_init()
{
	global $babDB;

	$aredit = array();
	$aredit['articles'] = false;
	$aredit['trash'] = false;

	if( $GLOBALS['BAB_SESS_USERID'] )
	{
		$arr = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and trash='Y'"));
		if( $arr['total'] != 0 )
		{
			$aredit['trash'] = true;
		}

		$arr = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and result!='".BAB_ART_STATUS_DRAFT."' and trash='N'"));
		if( $arr['total'] != 0 )
		{
			$aredit['articles'] = true;
		}
	}
	return $aredit;
}


function getHiddenUpload()
{
	require_once $GLOBALS['babInstallPath'].'utilit/hiddenUpload.class.php';
	
	$oHiddenForm = new bab_HiddenUploadForm();
	
	$oHiddenForm->addHiddenField('iIdDraft', bab_rp('iIdDraft', 0));
	$oHiddenForm->addHiddenField('tg', 'artedit');
	$oHiddenForm->addHiddenField('MAX_FILE_SIZE', $GLOBALS['babMaxImgFileSize']);
	$oHiddenForm->addHiddenField('idx', 'uploadDraftArticleImg');
	
	header('Cache-control: no-cache');
	die($oHiddenForm->getHtml());
}

	
function uploadDraftArticleImg()
{
	require_once dirname(__FILE__) . '/utilit/artincl.php';
	require_once dirname(__FILE__) . '/utilit/hiddenUpload.class.php';
	
	$iIdDraft		= (int) bab_rp('iIdDraft', 0);
	$sJSon			= '';
	$sKeyOfPhpFile	= 'articlePicture';
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');
	$oPubImpUpl		= new bab_PublicationImageUploader();
	$iIdDelegation	= 0; //Dummy value, i dont need this here
	
	$oEnvObj->setEnv($iIdDelegation);
	$sPath = $oEnvObj->getDraftArticleImgPath($iIdDraft);
	
	if(0 < $iIdDraft)
	{
		$aImageInfo = bab_getImageDraftArticle($iIdDraft);
		if(false !== $aImageInfo)
		{
			bab_deleteImageDraftArticle($iIdDraft);
			
			if(file_exists($sPath . $aImageInfo['name']))
			{
				@unlink($sPath . $aImageInfo['name']);
			}
		}
		$sFullPathName = $oPubImpUpl->uploadDraftArticleImage($iIdDraft, $sKeyOfPhpFile);
	}
	
	if(false === $sFullPathName)
	{
		$sMessage = implode(',', $oPubImpUpl->getError());
		if('utf8' == bab_charset::getDatabase())
		{
			$sMessage = utf8_encode($sMessage);
		}
		/*	
		$sJSon = json_encode(array(
				"success"  => false,
				"failure"  => true,
				"sMessage" => $sMessage));
		//*/
		$sJSon = '{"success":"false", "failure":"true", "sMessage":"' . $sMessage . '"}';
	}
	else
	{
		//Insérer l'image en base
		$aPathParts		= pathinfo($sFullPathName);
		$sName			= $aPathParts['basename'];
		$sPathName		= BAB_PathUtil::addEndSlash($aPathParts['dirname']);
		$sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));
		$sRelativePath	= mb_substr($sPathName, mb_strlen($sUploadPath), mb_strlen($sFullPathName) - mb_strlen($sName));
		
		bab_addImageToDraftArticle($iIdDraft, $sName, $sRelativePath);
		
		$sMessage = $sName;
		if('utf8' == bab_charset::getDatabase())
		{
			$sMessage = utf8_encode($sMessage);
		}
		/*	
		$sJSon = json_encode(array(
				"success"	=> true,
				"failure"	=> false,
				"sMessage"	=> $sMessage));
		//*/
		$sJSon = '{"success":"true", "failure":"false", "sMessage":"' . $sMessage . '"}';
		
		//bab_debug(bab_HiddenUploadForm::getHiddenIframeHtml($sJSon));		
	}
				
	header('Cache-control: no-cache');
	print bab_HiddenUploadForm::getHiddenIframeHtml($sJSon);		
}


function getImage()
{	
	require_once dirname(__FILE__) . '/utilit/artincl.php';
	require_once dirname(__FILE__) . '/utilit/gdiincl.php';

	$iIdDraft		= (int) bab_rp('iIdDraft', 0);
	$iIdArticle		= (int) bab_rp('iIdArticle', 0);
	$iWidth			= (int) bab_rp('iWidth', 0);
	$iHeight		= (int) bab_rp('iHeight', 0);
	$sImage			= (string) bab_rp('sImage', '');
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');
	$iIdDelegation	= 0; //Dummy value, i dont need this here
	
	global $babBody;
	$sPath = '';
	
	if(0 < $iIdDraft)
	{
		$oEnvObj->setEnv($iIdDelegation);
		$sPath = $oEnvObj->getDraftArticleImgPath($iIdDraft);
	}
	else
	{
		$iIdDelegation = bab_getArticleDelegationId($iIdArticle);
		if(false === $iIdDelegation)
		{
			return '???';
		}
		
		$oEnvObj->setEnv($iIdDelegation);
		$sPath = $oEnvObj->getArticleImgPath($iIdArticle);
	}
	
	$oImageResize = new bab_ImageResize();
	$oImageResize->resizeImageAuto($sPath . $sImage, $iWidth, $iHeight);
}


function deleteDraftImage()
{
	require_once dirname(__FILE__) . '/utilit/artincl.php';
	
	$iIdDraft		= (int) bab_rp('iIdDraft', 0);
	$iIdDelegation	= 0; //Dummy value, i dont need this here
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');
	
	$oEnvObj->setEnv($iIdDelegation);
	$sPath = $oEnvObj->getDraftArticleImgPath($iIdDraft);
	
	deleteDraftArticleImage($iIdDraft, $sPath);
	die('');
}


function deleteDraftArticleImage($iIdDraft, $sPathName)
{
	$aImageInfo = bab_getImageDraftArticle($iIdDraft);
	if(false !== $aImageInfo)
	{
		bab_deleteImageDraftArticle($iIdDraft);
		
		if(file_exists($sPathName . $aImageInfo['name']))
		{
			@unlink($sPathName . $aImageInfo['name']);
			@rmdir($sPathName);
		}
	}
}


//bab_debug($_REQUEST);


/* main */


$iNbSeconds = 2 * 86400; //2 jours
require_once dirname(__FILE__) . '/utilit/artincl.php';
bab_PublicationImageUploader::deleteOutDatedTempImage($iNbSeconds);

if('getImage' == bab_rp('idx', ''))
{
	getImage(); // called by ajax
	exit;
}

$artedit = array();

$baccess = false;
if(count(bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL)) == 0  && count(bab_getUserIdObjects(BAB_TOPICSMOD_GROUPS_TBL)) == 0)
{
	$ida = bab_rp('idart', 0);
	//Try to verify if current user can update article as manager or author 
	if( $ida )
	{
	$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, adt.id_article from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where adt.id='".$babDB->db_escape_string($ida)."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$rr = $babDB->db_fetch_array($res);
		if(( $rr['allow_update'] != '0' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID'])      
		|| ( $rr['allow_manupdate'] != '0' && bab_isAccessValidByUser(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'], $GLOBALS['BAB_SESS_USERID']))) {
			{
				$baccess = true;
			}
		}
		}
	}
}
else
{
	$baccess = true;
}

if( $baccess ) 
{
$idx = bab_rp('idx', 'list');

$rfurl = bab_rp('rfurl', "?tg=artedit&idx=list");

if( $updstep01 = bab_rp('updstep01'))
{

	if( $updstep01 == 'cancel')
	{
		$idx='unload';
		$refreshurl = $rfurl;
		$popupmessage = '';
	}
	elseif( $updstep01 == 'next')
	{
		$idx = 's01';
	}
}
elseif( $updstep02 = bab_rp('updstep02') )
{
	if( $updstep02 == 'cancel' )
	{
		$idx='unload';
		$refreshurl = $rfurl;
		$popupmessage = '';
	}
	elseif( $updstep02 == 'prev' )
	{
		$idx = 's00';
	}
	elseif( $updstep02 == 'next' )
	{
		$topicid = bab_pp('topicid', 0);
		$articleid = bab_pp('articleid', 0);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topicid."&article=".$articleid."&rfurl=".urlencode("?tg=artedit&idx=list"));
		exit;
	}
}
elseif( $updstep0 = bab_rp('updstep0') )
{
	if( $updstep0 == 'cancel' )
	{
		if( isset($_POST['idart']) && $_POST['idart'] != 0 )
			{
			deleteDraft($_POST['idart']);
			unset($_POST['idart']);
			}
		$idx='unload';
		$refreshurl = $rfurl;
		$popupmessage = '';
	}
}
elseif( $updstep1 = bab_rp('updstep1') )
{
	if( $updstep1 == 'cancel' )
	{
		if( isset($_POST['idart']) && $_POST['idart'] != 0 )
			{
			deleteDraft($_POST['idart']);
			unset($_POST['idart']);
			}
		$idx='unload';
		$refreshurl = $rfurl;
		$popupmessage = '';
	}
	elseif( $updstep1 == 'save' )
	{
		if( !isset($_POST['idart']) || $_POST['idart'] == 0 )
			{
			$idart = bab_newArticleDraft($topicid, $articleid);
			}
		else
			{
			$idart = bab_pp('idart', 0);
			}

		$approbid = bab_pp('approbid', 0);
		$message = '';
		if( $idart == 0 )
		{
			$message = bab_translate("Draft creation failed");
			$idx = 's0';
		}elseif(!updateArticleDraft($idart, bab_pp('title'), bab_pp('lang'), $approbid, $message))
		{
			$idx = 's1';
		}
		else
		{
		$idx='unload';
		$popupmessage = bab_translate("Update done");
		$refreshurl = $rfurl;
		}
	}
	elseif( $updstep1 == 'prev' )
	{
		$idx = 's0';
	}
	elseif( $updstep1 == 'submit' )
	{
		if( !isset($_POST['idart']) || $_POST['idart'] == 0 )
			{
			$idart = bab_newArticleDraft($topicid, $articleid);
			}
		else
			{
			$idart = bab_pp('idart', 0);
			}

		if( !isset($approbid)) { $approbid =0;}
		$message = '';
		if( $idart == 0 )
		{
			$message = bab_translate("Draft creation failed");
			$idx = 's0';
		}elseif(!updateArticleDraft($idart, $title, $lang, $approbid, $message))
		{
			$idx = 's1';
		}
		else
		{
		$message = '';
		if( !submitArticleDraft( $idart, $message) )
			{
			$idx = 's1';
			}
		else
			{
			$idx='unload';
			$popupmessage = bab_translate("Update done");
			$refreshurl = $rfurl;
			}
		}
	}
	elseif( $updstep1 == 'next' )
	{
		if( !isset($_POST['idart']) || $_POST['idart'] == 0 )
			{
			$idart = bab_newArticleDraft($topicid, $articleid);
			}
		else
			{
			$idart = bab_pp('idart', 0);
			}
		
		$approbid = bab_pp('approbid', 0);

		$message = '';
		if( $idart == 0 )
		{
			$message = bab_translate("Draft creation failed");
			$idx = 's0';
		}elseif(!updateArticleDraft($idart, bab_pp('title'), bab_pp('lang'), $approbid, $message))
		{
			$idx = 's1';
		}
		else
		{
			$idx = 's2';
		}
	}
}
elseif( $updstep2 = bab_rp('updstep2') )
{
	if( $updstep2 == 'cancel' )
	{
		if( isset($_POST['idart']) && $_POST['idart'] != 0 )
			{
			deleteDraft($_POST['idart']);
			unset($idart);
			unset($_POST['idart']);
			}
		$idx='unload';
		$refreshurl = $rfurl;
		$popupmessage = '';
	}
	elseif( $updstep2 == 'save' )
	{
		$idx='unload';
		if( isset($_POST['approbid'])) 
			{ 
			savePreviewDraft($_POST['idart'], $_POST['approbid']);
			}
		$popupmessage = bab_translate("Update done");
		$refreshurl = $rfurl;
	}
	elseif( $updstep2 == 'submit' )
	{
		$message = '';
		if( isset($_POST['approbid'])) 
			{ 
			savePreviewDraft($_POST['idart'], $_POST['approbid']);
			}

		if( !submitArticleDraft( $_POST['idart'], $message) )
			{
			$idx = 's2';
			}
		else
			{
			$idx='unload';
			$popupmessage = bab_translate("Update done");
			$refreshurl = $rfurl;
			}
	}
	elseif( $updstep2 == 'next' )
	{
		if( isset($_POST['approbid'])) 
			{
			savePreviewDraft($_POST['idart'], $_POST['approbid']);
			}
		$idx = 's3';
	}
	elseif( $updstep2 == 'prev' )
	{
		$idx = 's1';
	}
}
elseif( $updstep3 = bab_rp('updstep3') )
{
	if( $updstep3 == 'cancel' )
	{
		if( isset($_POST['idart']) && $_POST['idart'] != 0 )
			{
			deleteDraft($_POST['idart']);
			unset($idart);
			unset($_POST['idart']);
			}
		$idx='unload';
		$refreshurl = $rfurl;
		$popupmessage = '';
	}
	elseif( $updstep3 == 'fadd')
	{
		if( isset($_POST['idart']) && $_POST['idart'] != 0 )
		{
		$message = '';
		if( updateDocumentsArticleDraft($_POST['idart'], $message) )
			{
			//Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=s3&idart=".$_POST['idart']);
			//exit;
			$idx='s3';
			}
		else
			{
			$idx='s3';
			}
		}
		$idx='s3';
	}
	elseif( $updstep3 == 'delf')
	{
		if( isset($_GET['idart']) && $_GET['idart'] != 0 )
		{
		delDocumentArticleDraft( $_GET['idart'], $_GET['idf'] );
		//Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=s3&idart=".$_GET['idart']);
		}
		$idx='s3';
	}
	elseif( $updstep3 == 'proptop')
	{
		$idx='s3';
	}
	elseif( $updstep3 == 'save' )
	{
		$message = '';
		if(!updatePropertiesArticleDraft($message))
		{
			$idx = 's3';
		}
		else
		{
		$idx='unload';
		$popupmessage = bab_translate("Update done");
		$refreshurl = $rfurl;
		}
	}
	elseif( $updstep3 == 'submit' )
	{
		$message = '';
		if(!updatePropertiesArticleDraft($message))
		{
			$idx = 's3';
		}
		else
		{
		$message = '';
		if( !submitArticleDraft( $_POST['idart'], $message) )
			{
			$idx = 's3';
			}
		else
			{
			$idx='unload';
			$popupmessage = bab_translate("Update done");
			$refreshurl = $rfurl;
			}
		}
	}
	elseif( $updstep3 == 'prev' )
	{
		$idx = 's1';
	}
}

//Upload de l'image sans javascript
if(array_key_exists('imageSubmit', $_POST))
{
	require_once dirname(__FILE__) . '/utilit/artincl.php';
	require_once dirname(__FILE__) . '/utilit/hiddenUpload.class.php';
	
	$iIdDraft		= (int) bab_rp('idart', 0);
	$sKeyOfPhpFile	= 'articlePicture';
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');
	$oPubImpUpl		= new bab_PublicationImageUploader();
	$aFileInfo		= false;
	$iIdDelegation	= 0; //Dummy value, i dont need this here
	
	$oEnvObj->setEnv($iIdDelegation);
	$sPath = $oEnvObj->getDraftArticleImgPath($iIdDraft);

	if(0 < $iIdDraft)
	{
		if((array_key_exists($sKeyOfPhpFile, $_FILES) && '' != $_FILES[$sKeyOfPhpFile]['tmp_name']))
		{
			$sFullPathName = $oPubImpUpl->uploadDraftArticleImage($sKeyOfPhpFile);
			if(false !== $sFullPathName)
			{
				deleteDraftArticleImage($iIdDraft, $sPath);
				
				$aPathParts		= pathinfo($sFullPathName);
				$sName			= $aPathParts['basename'];
				$sPathName		= BAB_PathUtil::addEndSlash($aPathParts['dirname']);
				$sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));
				$sRelativePath	= mb_substr($sPathName, mb_strlen($sUploadPath), mb_strlen($sFullPathName) - mb_strlen($sName));
				
				bab_addImageToDraftArticle($iIdDraft, $sName, $sRelativePath);
				$_POST['sImgName'] = $sName;
				if((array_key_exists('deleteImageChk', $_POST)))
				{
					unset($_POST['deleteImageChk']);
				}
			}
		}
		else if(1 === (int) bab_rp('deleteImageChk', 0))
		{
			deleteDraftArticleImage($iIdDraft, $sPath);
			$_POST['sImgName'] = '';
			unset($_POST['deleteImageChk']);
		}
	}
}

		


if($idx == 'movet')
{
	$idart = bab_gp('idart', 0);
	if( $idart )
	{
	moveArticleDraftToTrash($idart);
	}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
	exit;
}elseif( $idx == 'restore')
{
	$idart = bab_gp('idart', 0);
	if( $idart && !restoreArticleDraft($idart))
	{
		$idx = 'ltrash';
	}
	else
	{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		exit;
	}
}elseif( $idx == 'empty')
{
	emptyTrash();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
	exit;
}elseif( $idx == 'delt')
{
	$idart = bab_gp('idart', 0);
	if( $idart )
	{
	deleteDraft($idart);
	}
	Header('Location: '. $GLOBALS['babUrlScript'].'?tg=artedit&idx=lsub');
	exit;
}elseif( $idx == 'rests')
{
	$idart = bab_gp('idart', 0);
	if($idart && !restoreRefusedArticleDraft($idart))
		{
		Header('Location: '. $GLOBALS['babUrlScript'].'?tg=artedit&idx=lsub');
		exit;
		}
	else
		{
		$idx = 'lsub';
		}
}
}
else
{
	$idx = 'denied';
}

switch($idx)
	{
	case 'getHiddenUpload': // called by ajax
		getHiddenUpload();
		exit;
	
	case 'uploadDraftArticleImg': // called by ajax
		uploadDraftArticleImg();
		exit;	
	
	case 'deleteDraftImage': // called by ajax
		deleteDraftImage();
		exit;

	case 'denied':
		$babBody->msgerror = bab_translate("Access denied");
		break;

	case "s00": // Selection of a topic for the modification of an article.
		$topicid = bab_rp('topicid');
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Choose the topic");
		$html = showTopicTree(BAB_ARTICLE_TREE_VIEW_MODIFY_ARTICLES, $topicid);
		$babBodyPopup->babecho($html);
		printBabBodyPopup();
		exit;
		break;

	case "s01":
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Choose the article");
		$topicid = bab_rp('topicid');
		showChoiceArticleModify($topicid);
		printBabBodyPopup();
		exit;
		break;

	case "s0": // Selection of a topic for the publication of an article.
		$topicid = bab_rp('topicid');
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Choose the topic");
		$html = showTopicTree(BAB_ARTICLE_TREE_VIEW_SUBMIT_ARTICLES, $topicid);
		$babBodyPopup->babecho($html);
		printBabBodyPopup();
		exit;
		break;

	case "s1":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		$babBodyPopup->title = bab_translate("Write article");
		showEditArticle();
		printBabBodyPopup();
		exit;
		break;

	case "s2":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		$babBodyPopup->title = bab_translate("Preview");
		showPreviewArticle($idart);
		printBabBodyPopup();
		exit;
		break;
	case "s3":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		$babBodyPopup->title = bab_translate("Set article properties");
		$idart = bab_rp('idart', 0);
		showSetArticleProperties($idart);
		printBabBodyPopup();
		exit;
		break;
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		if( !isset($popupmessage)) { $popupmessage ='';}
		if( !isset($refreshurl)) { $refreshurl = isset($rfurl)? $rfurl :'';}
		popupUnload($popupmessage, $refreshurl);
		exit;
	case "getf":
		$idart = bab_rp('idart', 0);
		$idf = bab_rp('idf', 0);
		getDocumentArticleDraft( $idart, $idf );
		exit;
		break;
	case "propa":
		$idart = bab_rp('idart', 0);
		propertiesArticle( $idart);
		exit;
		break;
	case "preview":
		$idart = bab_gp('idart', 0);
		previewArticleDraft($idart);
		exit;
		break;
	case "edit":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$idart = bab_rp('idart', 0);
		if( $idart )
		{
			$title = bab_gp('title');
			$lang = bab_gp('lang');
			$babBodyPopup->title = bab_translate("Article edition");
			editArticleDraft($idart, $title, $lang, $message);
			printBabBodyPopup();
			exit;
		}
		else
		{
			$babBodyPopup->title = bab_translate("Article edition");
			$babBodyPopup->msgerror = bab_translate("Access denied");
			printBabBodyPopup();
			exit;
		}
		break;
	case "ltrash":
		$arrinit = artedit_init();
		if( !$arrinit['trash'] )
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		exit;
		}
		$babBody->title = bab_translate("List of articles");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listDraftsInTrash();
		$babBody->addItemMenu("ltrash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=artedit&idx=ltrash");
		if( $arrinit['articles'] )
		{
		$babBody->addItemMenu("lsub", bab_translate("My Articles"), $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
		}
		break;
	case "lsub":
		$arrinit = artedit_init();
		if( !$arrinit['articles'] )
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		exit;
		}
		$babBody->title = bab_translate("List of submitted articles");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listSubmitedArticles();
		if( $arrinit['trash'] )
		{
		$babBody->addItemMenu("ltrash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=artedit&idx=ltrash");
		}
		$babBody->addItemMenu("lsub", bab_translate("My Articles"), $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
		break;
	case "sub":
		$idart = bab_rp('idart', 0);
		if( $idart && submitArticleDraft( $idart, $babBody->msgerror, true) )
			{
			//Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
			//exit;
			}
		$idx = "list";
		/* break; */
	case "list":
	default:
		$arrinit = artedit_init();
		$babBody->title = bab_translate("List of articles");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listDrafts();
		if( $arrinit['trash'] )
		{
		$babBody->addItemMenu("ltrash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=artedit&idx=ltrash");
		}
		if( $arrinit['articles'] )
		{
		$babBody->addItemMenu("lsub", bab_translate("My Articles"), $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
