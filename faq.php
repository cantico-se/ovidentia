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
 * @internal SEC1 NA 11/12/2006 FULL
*/
include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/treeincl.php';
include_once $babInstallPath.'utilit/imgincl.php';
require_once $babInstallPath . 'utilit/toolbar.class.php';

function isUserManager()
{
	global $faqinfo;
	if( isset($faqinfo['id']) && bab_isAccessValid(BAB_FAQMANAGERS_GROUPS_TBL,$faqinfo['id']))
	{
		return true;
	}
	return false;
}

function listCategories()
{
	global $babBody, $babDB;
	$arrid = array();
	class listCategoriesCls
	{

		var $arr = array();
		var $arrid = array();
		var $db;
		var $count;
		var $res;
		var $urlcategory;
		var $namecategory;

		function listCategoriesCls($arrid)
		{
			global $babDB;
			$this->count = count($arrid);
			$this->arrid = $arrid;
			$this->txtmanage = bab_translate('Manage');
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		}

		function getnext()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
			{
				$req = "select * from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($this->arrid[$i])."'";
				$res = $babDB->db_query($req);
				if( $res && $babDB->db_num_rows($res) > 0)
				{
					$this->arr = $babDB->db_fetch_array($res);
					$editor = new bab_contentEditor('bab_faq');
					$editor->setContent($this->arr['description']);
					$editor->setFormat($this->arr['description_format']);
					$this->description = $editor->getHtml();
					$this->urlcategory = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$this->arr['id']);
					$this->namecategory = bab_toHtml($this->arr['category']);
					$this->manage = false;
					$this->urlmanage = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=questions&idscat=0&item=".$this->arr['id']);
					if(bab_isAccessValid(BAB_FAQMANAGERS_GROUPS_TBL, $this->arr['id'])){
						$this->manage = true;
					}
				}
				$i++;
				return true;
			}
			else
				return false;
		}
	}


	$langFilterValue = bab_getInstance('babLanguageFilter')->getFilterAsInt();
	switch($langFilterValue)
	{
		case 2:
			$req = "select * from ".BAB_FAQCAT_TBL." where lang='".$babDB->db_escape_string($GLOBALS['babLanguage'])."' or lang='*' or lang = ''";
			if (isset($GLOBALS['babApplyLanguageFilter']) && $GLOBALS['babApplyLanguageFilter'] == 'loose')
				$req.= " or id_manager = '" .$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']). "'";
			break;
		case 1:
			$req = "select * from ".BAB_FAQCAT_TBL." where lang like '". $babDB->db_escape_like(mb_substr($GLOBALS['babLanguage'], 0, 2)) ."%' or lang='*' or lang = ''";
			if (isset($GLOBALS['babApplyLanguageFilter']) && $GLOBALS['babApplyLanguageFilter'] == 'loose')
				$req.= " or id_manager = '" .$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']). "'";
			break;
		case 0:
		default:
			$req = "select * from ".BAB_FAQCAT_TBL;
			break;
	}
	$req .= ' order by category asc';
	$res = $babDB->db_query($req);

	while( $row = $babDB->db_fetch_array($res))
	{
		if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']) || bab_isAccessValid(BAB_FAQMANAGERS_GROUPS_TBL, $row['id']))
		{
			array_push($arrid, $row['id']);
		}
	}

	$temp = new listCategoriesCls($arrid);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "categorylist"));

	return count($arrid);
}


function FaqTableOfContents($idcat)
{
	global $babBody;
	
	class FaqTableOfContentsCls
	{
		var $idcat;

		function FaqTableOfContentsCls($idcat)
		{
			global $babDB, $faqinfo;
			
			/* @var $I Func_Icons */
			$I = bab_functionality::get('Icons');
			$I->includeCss();
			
			$this->alpha = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
			$this->idcat = $idcat;
			$this->modifytxt = bab_translate("Modify");
			$this->faqname = '<span class="bab-faq-name">FAQ: </span>' . $faqinfo['category'];
			
			$GLOBALS['babBody']->addStyleSheet('toolbar.css');
			$sImgPath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/images/22x22/';
			
			$this->htmlToolBar = '';
			if( isUserManager())
			{
				$item = bab_rp('item');
				$toolbar = new BAB_Toolbar();

				$toolbar->addToolbarItem(
					new BAB_ToolbarItem(
						bab_translate("Add Question"),
						$GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$idcat,
						$sImgPath . 'help.png',
						bab_translate("Add Question"),
						bab_translate("Add Question"),
						''
					)
				);
				
				$toolbar->addToolbarItem(
					new BAB_ToolbarItem(
						bab_translate("Add sub category"),
						$GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$idcat,
						$sImgPath . 'folder-new.png',
						bab_translate("Add sub category"),
						bab_translate("Add sub category"),
						''
					)
				);
				$this->htmlToolBar = $toolbar->printTemplate();
			}
			
			$this->modifytxt = bab_translate("Modify");
			
			$sql = "SELECT bab_faq_trees.id as id, bab_faq_trees.id_parent as idp, bab_faq_subcat.name as name
					FROM
						bab_faq_trees,
						bab_faq_subcat
					WHERE bab_faq_subcat.id_cat = " . $babDB->quote($idcat) . "
					AND bab_faq_trees.id_user = " . $babDB->quote($idcat) . "
					AND bab_faq_subcat.id_node = bab_faq_trees.id
					ORDER BY bab_faq_trees.lf asc";
			
			$res = $babDB->db_query($sql);
			
			$first = false;
			$this->tree = array();
			while( $arr = $babDB->db_fetch_array($res)){
				$this->tree[$arr['id']] = array('name' => $arr['name'], 'idp' => $arr['idp']);
				if($arr['idp'] == 0){
					$first = $arr['id'];
				}
			}
			
			$sql = "SELECT id, id_subcat, question, response
					FROM bab_faqqr
					WHERE idcat = " . $babDB->quote($idcat) . "
					ORDER BY date_modification ASC";
				
			$res = $babDB->db_query($sql);
				
			$this->questions = array();
			while( $arr = $babDB->db_fetch_array($res)){
				$this->questions[$arr['id']] = array('question' => $arr['question'], 'response' => $arr['response'], 'id_subcat' => $arr['id_subcat']);
			}
			
			if($first){
				$this->htmltree = $this->getTree($first, '', '');
			}
			
			 
		}

		function getTree($id, $number, $hierarchic)
		{
			$return = '';
			
			if($this->tree[$id]['name']){
				$return.= '<li>';
				$return.= '<div class="bab-faq-subcateg line">';
				$return.= $hierarchic.$number.' ';
				$return.= '<div class="bab-faq-category-text '.Func_Icons::PLACES_FOLDER.' icon">' . $this->tree[$id]['name'].'</div>';
				
				$return.= '<span class="bab-faq-actions" style="padding-top: 2px;">';
				
				//ADD A CATEGORY
				$url = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$this->idcat."&idscat=".$id);
				$img = '<img src="'.$GLOBALS['babSkinPath'].'images/22x22/folder-new.png"  alt="'.bab_translate("Add sub category").'" />';
				$return.= '<a title="'.bab_translate("Add sub category").'" href="'.$url.'">'.$img.'</a>';

				//ADD A QUESTION
				$url = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$this->idcat."&idscat=".$id);
				$img = '&nbsp;&nbsp;&nbsp;<img src="'.$GLOBALS['babSkinPath'].'images/22x22/add.png"  alt="'.bab_translate("Add Question").'" />';
				$return.= '<a title="'.bab_translate("Add Question").'" href="'.$url.'">'.$img.'</a>';
				
				//EDIT A CATEGORY
				$url = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=ModifyC&item=".$this->idcat."&ids=".$id);
				$img = '&nbsp;&nbsp;<img src="'.$GLOBALS['babSkinPath'].'images/22x22/edit.png"  alt="'.$this->modifytxt.'" />';
				$return.= ' <a title="'.$this->modifytxt.'" href="'.$url.'">'.$img.'</a>';
				
				$return.= '</span>';
				
				$return.= '</div>';
				
			}
			$questions = $this->getQuestion($id, $hierarchic.$number);
			if($questions){
				$return.= '<ul class="'.Func_Icons::ICON_LEFT_24.'">'.$questions.'</ul>';
			}
			$ul = true;
			$subNumber = 0;
			foreach($this->tree as $k => $v){
				if($v['idp'] == $id){
					$subNumber++;
					if($ul){
						$return.= '<ul class="'.Func_Icons::ICON_LEFT_24.'">';
						$ul = false;
					}
					$return.= $this->getTree($k, $subNumber.'.', $hierarchic.$number);
					
				}
			}
			if(!$ul){
				$return.= '</ul>';
			}
			if($this->tree[$id]['name']){
				$return.= '</li>';
			}
			
			
			return $return;
		}
		
		function getQuestion($id, $hierarchic){
			$return = '';
			$num = 0;
			foreach($this->questions as $k => $v){
				if($v['id_subcat'] == $id){
					$return.= '<li class="bab-faq-question line">';
					$return.= $hierarchic . $this->alpha[$num] . '.<div class="bab-faq-question-text '.Func_Icons::APPS_FAQS.' icon">' . $v['question'].'</div>';

					$return.= '<span class="bab-faq-actions" style="padding-top: 2px; float: right;">';
					//EDIT QUESTION
					$url = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$this->idcat."&idscat=".$id."&idq=".$k);
					$img = '&nbsp;&nbsp;<img src="'.$GLOBALS['babSkinPath'].'images/22x22/edit.png"  alt="'.$this->modifytxt.'" />';
					$return.= '<a title="'.$this->modifytxt.'" href="'.$url.'">'.$img.'</a>';
					
					//REMOVE QUESTION
					$url = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=Delete&item=".$this->idcat."&idscat=".$id."&idq=".$k);
					$img = '&nbsp;&nbsp;<img src="'.$GLOBALS['babSkinPath'].'images/22x22/delete.png"  alt="'.$this->modifytxt.'" />';
					$return.= '<a title="'.bab_translate('Remove').'" href="'.$url.'">'.$img.'</a>';
										
					$return.= '</span>';
					
					$return.= '</li>';
					$num++;
				}
			}
			
			return $return;
		}


	}
	
	$GLOBALS['babBody']->addStyleSheet('faq.css');
	$temp = new FaqTableOfContentsCls($idcat);
	$babBody->babecho(bab_printTemplate($temp,"faq.html", "tableofcontents"));
	return true;
}


function FaqPrintContents($idcat)
{
	global $babBody;
	class FaqPrintContentsCls
	{
		var $idcat;

		function FaqPrintContentsCls($idcat)
		{
			global $babDB, $faqinfo;
			
			/* @var $I Func_Icons */
			$I = bab_functionality::get('Icons');
			$I->includeCss();
			
			$this->idcat = $idcat;
			$this->item = bab_toHtml($idcat);
			$this->faqname = '<span class="bab-faq-name">FAQ: </span>' . $faqinfo['category'];
			$this->contentsname = bab_translate("CONTENTS");
			$this->t_print = bab_translate("Print Friendly");
			$this->return = bab_translate("Go to Top");
			$this->babTree  = new bab_arraytree(BAB_FAQ_TREES_TBL, $idcat, "");
			$this->arr = array();
			reset($this->babTree->nodes);
			$this->maxlevel = 0;
			while( $row=each($this->babTree->nodes) )
			{
				$this->arr[$row[1]['id']] = $row[1]['lf'];
				if( $row[1]['level'] > $this->maxlevel )
				{
					$this->maxlevel = $row[1]['level'];
				}
			}
			bab_sort::asort($this->arr);
			reset($this->arr);
			$this->arr = array_keys($this->arr);
			$this->maxlevel += 1;
			$this->padarr = array();

			if( isUserManager())
			{
				$this->update = true;
			}
			else
			{
				$this->update = false;
			}

			$this->res = $babDB->db_query("select fst.* from ".BAB_FAQ_SUBCAT_TBL." fst LEFT JOIN ".BAB_FAQ_TREES_TBL." ftt on ftt.id=fst.id_node where id_cat='".$babDB->db_escape_string($this->idcat)."' and ftt.id_user='".$babDB->db_escape_string($this->idcat)."' order by ftt.lf asc");
			$this->count = $babDB->db_num_rows($this->res);
			$this->arrquestions = array();
			$this->bresponse = 0;
			
			$this->alpha = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
			$this->idcat = $idcat;
			$this->modifytxt = bab_translate("Modify");
			
			
			$sImgPath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/images/22x22/';
			
			$this->modifytxt = bab_translate("Modify");
			
			$sql = "SELECT bab_faq_trees.id as id, bab_faq_trees.id_parent as idp, bab_faq_subcat.name as name
					FROM
						bab_faq_trees,
						bab_faq_subcat
					WHERE bab_faq_subcat.id_cat = " . $babDB->quote($idcat) . "
					AND bab_faq_trees.id_user = " . $babDB->quote($idcat) . "
					AND bab_faq_subcat.id_node = bab_faq_trees.id
					ORDER BY bab_faq_trees.lf asc";
			
			$res = $babDB->db_query($sql);
			
			$first = false;
			$this->tree = array();
			while( $arr = $babDB->db_fetch_array($res)){
				$this->tree[$arr['id']] = array('name' => $arr['name'], 'idp' => $arr['idp']);
				if($arr['idp'] == 0){
					$first = $arr['id'];
				}
			}
			
			$sql = "SELECT id, id_subcat, question, response
					FROM bab_faqqr
					WHERE idcat = " . $babDB->quote($idcat) . "
					ORDER BY date_modification ASC";
				
			$res = $babDB->db_query($sql);
				
			$this->questions = array();
			while( $arr = $babDB->db_fetch_array($res)){
				$this->questions[$arr['id']] = array('question' => $arr['question'], 'response' => $arr['response'], 'id_subcat' => $arr['id_subcat']);
			}
			
			if($first){
				$this->htmltree = $this->getTree($first, '', '');
				$this->htmltreereponse = $this->getTree($first, '', '', true);
			}
		}

		function getTree($id, $number, $hierarchic, $reponse = false)
		{
			$return = '';
			
			if($this->tree[$id]['name']){
				$return.= '<li>';
				$return.= '<div class="bab-faq-subcateg line">';
				$return.= $hierarchic.$number.' ';
				$return.= '<div class="bab-faq-category-text '.Func_Icons::PLACES_FOLDER.' icon">' . $this->tree[$id]['name'].'</div>';				
				$return.= '</div>';
				
			}
			$questions = $this->getQuestion($id, $hierarchic.$number, $reponse);
			if($questions){
				$class = '';
				if (empty($_GET['popup']))
				{
					$class = Func_Icons::ICON_LEFT_24;
				}
				$return.= '<ul class="'.$class.'">'.$questions.'</ul>';
			}
			$ul = true;
			$subNumber = 0;
			foreach($this->tree as $k => $v){
				if($v['idp'] == $id){
					$subNumber++;
					if($ul){
						$class = '';
						if (empty($_GET['popup']))
						{
							$class = Func_Icons::ICON_LEFT_24;
						}
						$return.= '<ul class="'.$class.'">';
						$ul = false;
					}
					$return.= $this->getTree($k, $subNumber.'.', $hierarchic.$number, $reponse);
					
				}
			}
			if(!$ul){
				$return.= '</ul>';
			}
			if($this->tree[$id]['name']){
				$return.= '</li>';
			}
			
			
			return $return;
		}
		
		function getQuestion($id, $hierarchic, $reponse = false){
			$return = '';
			$num = 0;
			foreach($this->questions as $k => $v){
				if($v['id_subcat'] == $id){
					if(!$reponse){
						$return.= '<li class="bab-faq-question line">';
						$return.= '<a class="bab-faq-travel-link" href="?tg=faq&idx=Print&item='.$this->idcat.'#'.$k.'">';
						$return.= $hierarchic . $this->alpha[$num] . '.<span style="padding-right: 40px;" class="bab-faq-question-text '.Func_Icons::APPS_FAQS.' icon">' . $v['question'].'</span>';
						$return.= '</a>';
					}else{
						$return.= '<li class="bab-faq-respons">';
						$return.= '<div class="bab-faq-question-respons">';
						$return.= '<a name="'.$k.'"></a>';
						$return.= $hierarchic . $this->alpha[$num] . '.<span style="padding-right: 70px;" class="bab-faq-question-text '.Func_Icons::APPS_FAQS.' icon">' . $v['question'].'</span>';
						
						$return.= '<span class="bab-faq-actions" style="padding-top: 2px; float: right;">';
						
						//GO TOP
						$url = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$this->idcat."&idscat=".$id."&idq=".$k);
						$img = '&nbsp;&nbsp;<img src="'.$GLOBALS['babSkinPath'].'images/22x22/go-up.png"  alt="'.$this->modifytxt.'" />';
						$return.= '<a title="'.$this->return.'" href="#top">'.$img.'</a>';
						
						$return.= '</span>';
						
						$return.= '</div>';
						$return.= '<div style="margin-left: 40px;">';
						$return.= $v['response'].'<br />';
						$return.= '</div>';
					}
					$return.= '</li>';
					$num++;
				}
			}
			
			return $return;
		}
	}

	
	$temp = new FaqPrintContentsCls($idcat);
	if (empty($_GET['popup']))
	{
		/* @var $I Func_Icons */
		$I = bab_functionality::get('Icons');
		$I->includeCss();
		$GLOBALS['babBody']->addStyleSheet('faq.css');
		$babBody->babecho(bab_printTemplate($temp,"faqprint.html", "contents"));
	}
	else
	{
		include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
		$GLOBALS['babBodyPopup'] = new babBodyPopup();
		$GLOBALS['babBodyPopup']->addStyleSheet('faq.css');
		$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
		$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
		$GLOBALS['babBodyPopup']->babecho( bab_printTemplate($temp,"faqprint.html", "contents") );
		printBabBodyPopup();
		die();
	}
	return true;
}

function listSubCategoryQuestions($idcat, $idscat)
{
	global $babBody;
	class listSubCategoryQuestionsCls
	{

		var $arr1 = array();
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $index=0;
		var $return;
		var $indexquestions;
		var $sitename;
		var $urlsite;

		function listSubCategoryQuestionsCls($idcat, $idscat)
		{
			global $babDB, $faqinfo;
			$this->return = bab_translate("Go to Top");
			$this->faqname = bab_toHtml($faqinfo['category']);
			list($this->subcatname) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_FAQ_SUBCAT_TBL." where id='".$babDB->db_escape_string($idscat)."'"));
			$this->subcatname = bab_toHtml($this->subcatname);
			$req = "select * from ".BAB_FAQQR_TBL." where idcat='".$babDB->db_escape_string($idcat)."' and id_subcat='".$babDB->db_escape_string($idscat)."'";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
				
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			$GLOBALS['babBody']->addStyleSheet('toolbar.css');
			$sImgPath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/images/22x22/';
			
			$this->htmlToolBar = '';
			if( isUserManager())
			{
				$item = bab_rp('item');
				$toolbar = new BAB_Toolbar();

				$toolbar->addToolbarItem(
					new BAB_ToolbarItem(
						bab_translate("Add Question"),
						$GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$item."&idscat=".$idscat,
						$sImgPath . 'help.png',
						bab_translate("Add Question"),
						bab_translate("Add Question"),
						''
					)
				);
				
				$toolbar->addToolbarItem(
					new BAB_ToolbarItem(
						bab_translate("Add sub category"),
						$GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$item."&idscat=".$idscat,
						$sImgPath . 'category.png',
						bab_translate("Add sub category"),
						bab_translate("Add sub category"),
						''
					)
				);
				$this->htmlToolBar = $toolbar->printTemplate();
			}
		}

		function getnext()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->question = bab_toHtml($arr['question']);
				$this->idq = bab_toHtml($arr['id']);
				$GLOBALS['babWebStat']->addFaqsQuestion($arr['id']);
				$i++;
				$this->index++;
				return true;
			}
			else
			{
				if( $this->count > 0 )
				{
					$babDB->db_data_seek($this->res, 0);
				}
				$this->index = 0;
				return false;
			}
		}

		function getnextbis()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->question = bab_toHtml($arr['question']);

				$editor = new bab_contentEditor('bab_faq_response');
				$editor->setContent($arr['response']);
				$editor->setFormat($arr['response_format']);
				$this->response = $editor->getHtml();
					
				$this->idq = bab_toHtml($arr['id']);
				$i++;
				$this->index++;
				return true;
			}
			else
				return false;
		}
	}
	
	$temp = new listSubCategoryQuestionsCls($idcat, $idscat);
	$babBody->babecho(bab_printTemplate($temp,"faq.html", "subcatquestions"));
}



function viewQuestion($idcat, $idscat, $id)
{
	global $babBody;
	class viewQuestionCls
	{
		var $arr = array();
		var $db;
		var $res;
		var $return;
		var $returnurl;

		function viewQuestionCls($idcat, $idscat, $id)
		{
			global $babDB;
			$req = "select * from ".BAB_FAQQR_TBL." where id='".$babDB->db_escape_string($id)."'";
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
				
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				
			$editor = new bab_contentEditor('bab_faq_response');
			$editor->setContent($this->arr['response']);
			$editor->setFormat($this->arr['response_format']);
			$this->arr['response'] = $editor->getHtml();
				
			$this->returnurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$idcat."&idscat=".$idscat);
			$this->return = bab_translate("Return to Questions");
		}

	}

	$temp = new viewQuestionCls($idcat, $idscat, $id);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "viewquestion"));
	return true;
}

function viewPopupQuestion($id)
{
	global $babBody;

	class viewPopupQuestionCls
	{

		var $arr = array();
		var $db;
		var $res;
		var $more;
		var $close;
		var $sContent;

		function viewPopupQuestionCls($id)
		{
			global $babDB;
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			$this->close	= bab_translate("Close");
			$req			= "select * from ".BAB_FAQQR_TBL." where id='".$babDB->db_escape_string($id)."'";
			$res			= $babDB->db_query($req);
			$this->arr		= $babDB->db_fetch_array($res);
				
			if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $this->arr['idcat']) || isUserManager())
			{
				$GLOBALS['babWebStat']->addFaqsQuestion($id);

				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
					
				$editor = new bab_contentEditor('bab_faq_response');
				$editor->setContent($this->arr['response']);
				$editor->setFormat($this->arr['response_format']);
				$this->arr['response'] = $editor->getHtml();
			}
			else
			{
				$this->arr['question'] = '';
				$this->arr['response'] = bab_translate("Access denied");
			}
		}
	}

	$temp = new viewPopupQuestionCls($id);
	echo bab_printTemplate($temp,"faq.html", "popupquestion");
}

function faqPrint($idcat, $idscat)
{
	global $babBody;
	class faqPrintCls
	{

		var $arr1 = array();
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $index=0;
		var $return;
		var $indexquestions;
		var $sitename;
		var $urlsite;
		var $sContent;

		function faqPrintCls($idcat, $idscat)
		{
			global $babDB, $faqinfo;
				
			$this->sContent			= 'text/html; charset=' . bab_charset::getIso();
			$this->return			= bab_translate("Go to Top");
			$this->indexquestions	= bab_translate("Index of questions");
			$this->faqname			= $faqinfo['category'];
				
			if( !empty($idscat) )
			{
				list($this->subcatname) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_FAQ_SUBCAT_TBL." where id='".$babDB->db_escape_string($idscat)."'"));
			}
			else
			{
				$this->subcatname = '';
				list($idscat) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_FAQ_SUBCAT_TBL." where id_node='".$babDB->db_escape_string($faqinfo['id_root'])."'"));
			}
			$req = "select * from ".BAB_FAQQR_TBL." where idcat='".$babDB->db_escape_string($idcat)."' and id_subcat='".$babDB->db_escape_string($idscat)."'";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
				
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		}

		function getnext()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->idq = $arr['id'];
				$this->question = $arr['question'];
				$GLOBALS['babWebStat']->addFaqsQuestion($arr['id']);
				$i++;
				$this->index++;
				return true;
			}
			else
			{
				if( $this->count > 0 )
				{
					$babDB->db_data_seek($this->res, 0);
				}
				$this->index = 0;
				return false;
			}
		}

		function getnextbis()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->idq = $arr['id'];
				$this->question = $arr['question'];
					
				$editor = new bab_contentEditor('bab_faq_response');
				$editor->setContent($arr['response']);
				$editor->setFormat($arr['response_format']);
				$this->response = $editor->getHtml();

				$i++;
				$this->index++;
				return true;
			}
			else
				return false;
		}
	}

	$temp = new faqPrintCls($idcat, $idscat);
	echo bab_printTemplate($temp,"faqprint.html", "subcategory");
}


function addQuestion($idcat, $idscat)
{
	global $babBody;
	class addQuestionCls
	{
		var $question;
		var $response;
		var $add;
		var $idcat;

		function addQuestionCls($idcat, $idscat)
		{
			global $babDB;
			$this->subcattxt = bab_translate("Sub category");
			$this->question = bab_translate("Question");
			$this->response = bab_translate("Response");
			$this->add = bab_translate("Add");
			$this->idcat = bab_toHtml($idcat);
			$this->idscat = $idscat;
				
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				
			$editor = new bab_contentEditor('bab_faq_response');
			$editor->setParameters(array('height' => 400));
			$this->editor = $editor->getEditor();
				
			$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$babDB->db_escape_string($idcat)."'");
			$this->count = $babDB->db_num_rows($this->res);
		}

		function getnextsubcat()
		{
			global $babDB, $faqinfo;
			static $i = 0;
			if( $i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->idsubcat = bab_toHtml($arr['id']);
				$this->subcatname = $arr['name'];
				if( empty($this->subcatname))
				{
					$this->subcatname = $faqinfo['category'];
				}

				$this->subcatname = bab_toHtml($this->subcatname);
				if( $arr['id'] == $this->idscat)
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
				return false;
		}
	}

	$temp = new addQuestionCls($idcat, $idscat);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admquestioncreate"));
}

function addSubCategory($idcat, $idscat)
{
	global $babBody;
	class addSubCategoryCls
	{
		var $question;
		var $response;
		var $add;
		var $idcat;

		function addSubCategoryCls($idcat, $idscat)
		{
			global $babDB;
			$this->subcat = bab_translate("Sub category");
			$this->add = bab_translate("Add");
			$this->idcat = bab_toHtml($idcat);
			$this->idscat = bab_toHtml($idscat);
			$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$babDB->db_escape_string($idcat)."'");
			$this->count = $babDB->db_num_rows($this->res);
		}

		function getnextsubcat()
		{
			global $babDB, $faqinfo;
			static $i = 0;
			if( $i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->idsubcat = bab_toHtml($arr['id']);
				$this->subcatname = $arr['name'];
				if( empty($this->subcatname))
				{
					$this->subcatname = $faqinfo['category'];
				}
				$this->subcatname = bab_toHtml($this->subcatname);
				if( $this->idsubcat == $this->idscat)
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
				return false;
		}
	}

	$temp = new addSubCategoryCls($idcat, $idscat);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admsubcatcreate"));
}



function modifyQuestion($item, $idscat, $idq)
{
	global $babBody;
	if( !isset($idq))
	{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid question !!");
		return;
	}
	class modifyQuestionCls
	{
		var $questiontxt;
		var $responsetxt;
		var $add;
		var $idcat;

		var $db;
		var $arr = array();
		var $res;

		function modifyQuestionCls($idcat, $idscat, $idq)
		{
			global $babDB;
			$this->questiontxt = bab_translate("Question");
			$this->responsetxt = bab_translate("Response");
			$this->subcattxt = bab_translate("Sub category");
			$this->add = bab_translate("Update Question");
			$this->idcat = bab_toHtml($idcat);
			$this->idscat = bab_toHtml($idscat);
			$this->idq = bab_toHtml($idq);
			$req = "select * from ".BAB_FAQQR_TBL." where id='".$babDB->db_escape_string($idq)."'";
			$res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_array($res);
			$this->question = bab_toHtml($arr['question']);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				
			$editor = new bab_contentEditor('bab_faq_response');
			$editor->setContent($arr['response']);
			$editor->setFormat($arr['response_format']);
			$editor->setParameters(array('height' => 400));
			$this->editor = $editor->getEditor();

			$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$babDB->db_escape_string($idcat)."'");
			$this->count = $babDB->db_num_rows($this->res);
		}

		function getnextsubcat()
		{
			global $babDB;
			global $faqinfo;
			static $i = 0;
			if( $i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->idsubcat = bab_toHtml($arr['id']);
				$this->subcatname = $arr['name'];
				if( empty($this->subcatname))
				{
					$this->subcatname = $faqinfo['category'];
				}
				$this->subcatname = bab_toHtml($this->subcatname);

				if( $arr['id'] == $this->idscat )
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
				return false;
		}
	}
	$temp = new modifyQuestionCls($item, $idscat, $idq);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admquestionmodify"));
}

function deleteQuestion($item, $idq)
{
	global $babBody;

	class deleteQuestionCls
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

		function deleteQuestionCls($item, $idq)
		{
			$this->message = bab_translate("Are you sure you want to delete this question");
			$this->title = "";
			$this->warning = bab_translate("WARNING: This operation will delete question and its response"). "!";
			$this->urlyes = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idq=".$idq."&action=Yes");
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$item."&idq=".$idq);
			$this->no = bab_translate("No");
		}
	}

	$temp = new deleteQuestionCls($item, $idq);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
}

function modifySubCategory($idcat, $idscat, $ids)
{
	global $babBody;
	class modifySubCategoryCls
	{
		var $question;
		var $response;
		var $add;
		var $idcat;
		var $del;

		function modifySubCategoryCls($idcat, $idscat, $ids)
		{
			global $babDB;
			$this->subcat = bab_translate("Sub category");
			$this->add = bab_translate("Modify");
			$this->idcat = bab_toHtml($idcat);
			$this->idscat = bab_toHtml($idscat);
			$this->ids = bab_toHtml($ids);
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id='".$babDB->db_escape_string($ids)."'"));
			$this->subcatval = bab_toHtml($arr['name']);
			$this->bdelete = false;
			list($countq) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_FAQQR_TBL." where idcat='".$babDB->db_escape_string($idcat)."' and id_subcat='".$babDB->db_escape_string($ids)."'"));
			if( !$countq )
			{
				$babTree = new bab_arraytree(BAB_FAQ_TREES_TBL, $idcat);
				if( !$babTree->hasChildren($arr['id_node']))
				{
					$this->bdelete = true;
					$this->del = bab_translate("Delete");
				}
			}
		}
	}

	$temp = new modifySubCategoryCls($idcat, $idscat, $ids);
	$babBody->babecho( bab_printTemplate($temp,"faq.html", "admsubcatmodify"));
}


function saveQuestion($item, $idscat, $question)
{
	global $babDB, $faqinfo;


	include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
		
	$editor = new bab_contentEditor('bab_faq_response');
	$response = $editor->getContent();
	$responseFormat = $editor->getFormat();


	if( empty($question) || empty($response))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide question and response !!");
		return;
	}



	if( empty($idscat))
	{
		$idscat = $faqinfo['id_root'];
	}

	$query = "insert into ".BAB_FAQQR_TBL." (idcat, id_subcat, question, date_modification, id_modifiedby) values ('" .$babDB->db_escape_string($item). "', '" .$babDB->db_escape_string($idscat). "', '" .$babDB->db_escape_string($question). "', now(), '" .$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']). "')";
	$babDB->db_query($query);
	$id = $babDB->db_insert_id();

	$ar = array();

	$response = imagesReplace($response, $id."_faq_", $ar);

	$query = "update ".BAB_FAQQR_TBL." set response='".$babDB->db_escape_string($response)."', response_format='".$babDB->db_escape_string($responseFormat)."' where id='".$babDB->db_escape_string($id)."'";
	$babDB->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
}

function saveSubCategory($item, $idscat, $subcat)
{
	global $babDB, $faqinfo;

	if( empty($subcat) )
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
	}

	if( empty($idscat))
	{
		$idscat = $faqinfo['id_root'];
	}

	$babTree = new bab_dbtree(BAB_FAQ_TREES_TBL, $item);
	list($idnode) = $babDB->db_fetch_array($babDB->db_query("select id_node from ".BAB_FAQ_SUBCAT_TBL." where id='".$babDB->db_escape_string($idscat)."'"));
	$idnode = $babTree->add($idnode);

	$query = "insert into ".BAB_FAQ_SUBCAT_TBL." (id_cat, id_node, name) values ('" .$babDB->db_escape_string($item). "', '" .$babDB->db_escape_string($idnode). "', '" .$babDB->db_escape_string($subcat). "')";
	$babDB->db_query($query);
}

function updateSubCategory($item, $idscat, $ids, $subcat)
{
	global $babDB, $faqinfo;

	if( empty($subcat) )
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
	}

	$query = "update ".BAB_FAQ_SUBCAT_TBL." set name='".$babDB->db_escape_string($subcat)."' where id='".$babDB->db_escape_string($ids)."'";
	$babDB->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
}

function deleteSubCategory($item, $idscat, $ids)
{
	global $babDB, $faqinfo;

	list($idnode) = $babDB->db_fetch_array($babDB->db_query("select id_node from ".BAB_FAQ_SUBCAT_TBL." where id='".$babDB->db_escape_string($ids)."'"));
	$babTree = new bab_dbtree(BAB_FAQ_TREES_TBL, $item);
	if( $babTree->remove($idnode) )
	{
		$babDB->db_query("delete from ".BAB_FAQ_SUBCAT_TBL." where id='".$babDB->db_escape_string($ids)."'");
		$babDB->db_query("delete from ".BAB_FAQQR_TBL." where idcat='".$babDB->db_escape_string($item)."' and id_subcat='".$babDB->db_escape_string($ids)."'");
	}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
}

function updateQuestion($idq, $newidscat, $question)
{
	global $babDB;

	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		
	$editor = new bab_contentEditor('bab_faq_response');
	$response = $editor->getContent();

	if( empty($question) || empty($response))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide question and response !!");
		return;
	}

	$ar = array();

	$response = imagesReplace($response, $idq."_faq_", $ar);


	$query = "update ".BAB_FAQQR_TBL." set question='".$babDB->db_escape_string($question)."', response='".$babDB->db_escape_string($response)."', id_subcat='".$babDB->db_escape_string($newidscat)."', date_modification=now(), id_modifiedby='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' where id = '".$babDB->db_escape_string($idq)."'";
	$babDB->db_query($query);

}

function confirmDeleteQuestion($item, $idq)
{
	global $babDB;
	$arr = $babDB->db_fetch_array($babDB->db_query("select response from ".BAB_FAQQR_TBL." where id='".$babDB->db_escape_string($idq)."'"));
	deleteImages($arr['response'], $idq, "faq");
	$req = "delete from ".BAB_FAQQR_TBL." where id = '".$babDB->db_escape_string($idq)."'";
	$res = $babDB->db_query($req);
}


/* main */
$idx = bab_rp('idx', 'Categories');
$item = bab_rp('item');
$idscat = bab_rp('idscat', 0);
if($item)
{
	$faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($item)."'"));
}
else
{
	$faqinfo = array();
}


if( isUserManager() )
{
	if( 'addquestion' == bab_pp('addquestion'))
	{
		saveQuestion($item, bab_pp('newidscat'),  bab_pp('question'));
	}
	else if( 'updatequestion' == bab_pp('updatequestion'))
	{
		updateQuestion(bab_pp('idq'), bab_pp('newidscat'), bab_pp('question'));
	}
	else if( 'Yes' == bab_gp('action'))
	{
		confirmDeleteQuestion($item, bab_gp('idq'));
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
	}
	else if( 'addscat' == bab_pp('addsc'))
	{
		saveSubCategory($item, bab_pp('newidscat'), bab_pp('subcat'));
	}
	else if( 'modscat' == bab_pp('modsc'))
	{
		if( isset($_POST['bdel']))
		{
			deleteSubCategory($item, $idscat, bab_pp('ids'));
		}
		else
		{
			updateSubCategory($item, $idscat, bab_pp('ids'), bab_pp('subcat'));
		}
	}
}

switch($idx)
{
	case "questions":
		$babBody->title = bab_translate("Management");
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item) || bab_isAccessValid(BAB_FAQMANAGERS_GROUPS_TBL, $item))
		{
			$GLOBALS['babWebStat']->addFaq($item);
			FaqTableOfContents($item);
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			$babBody->addItemMenu("Print Friendly", bab_translate("Visualisation"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$item);
			$babBody->addItemMenu("questions", bab_translate("Management"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
		}
		break;

	case "listq":
		$babBody->title = $faqinfo['category'];
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item) || bab_isAccessValid(BAB_FAQMANAGERS_GROUPS_TBL, $item))
		{
			$GLOBALS['babWebStat']->addFaq($item);
			listSubCategoryQuestions($item, $idscat);
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			$babBody->addItemMenu("Print Friendly", bab_translate("Visualisation"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$item."&idscat=".$idscat);
			
			if( isUserManager()){
				$babBody->addItemMenu("questions", bab_translate("Management"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			}
			$babBody->addItemMenu("listq", bab_translate("Questions"),$GLOBALS['babUrlScript']."?tg=faq&idx=listq&item=".$item."&idscat=".$idscat);
		}
		break;

	case "viewpq":
		viewPopupQuestion(bab_gp('idq'));
		exit;

	case "viewq":
		$babBody->title = $faqinfo['category'];
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item) || bab_isAccessValid(BAB_FAQMANAGERS_GROUPS_TBL, $item))
		{
			$idq = bab_rp('idq');
			viewQuestion($item, $idscat, $idq);
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			if( isUserManager()){
				$babBody->addItemMenu("questions", bab_translate("Management"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			}
			$babBody->addItemMenu("Print Friendly", bab_translate("Visualisation"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenuAttributes("Print Friendly", "target=_blank");
			if( isUserManager())
				$babBody->addItemMenu("ModifyQ", bab_translate("Edit"),$GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$item."&idscat=".$idscat."&idq=".$idq);
		}
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete question");
		if( isUserManager())
		{
			$idq = bab_rp('idq');
			deleteQuestion($item, $idq);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=faq&idx=Delete&item=$item&idq=$idq");
		}
		break;

	case "addq":
		$babBody->title = bab_translate("Add question");
		if( isUserManager())
		{
			addQuestion($item, $idscat);
			$babBody->addItemMenu("questions", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
			$babBody->addItemMenu("addq", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$item);
			$babBody->addItemMenu("addsc", bab_translate("Add sub category"), $GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$item);
		}
		break;

	case "addsc":
		$babBody->title = bab_translate("Add sub category");
		if( isUserManager())
		{
			addSubCategory($item, $idscat);
			$babBody->addItemMenu("questions", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
			$babBody->addItemMenu("addq", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$item);
			$babBody->addItemMenu("addsc", bab_translate("Add sub category"), $GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$item);
		}
		break;

	case "ModifyQ":
		$babBody->title = bab_translate("Modify question");
		if( isUserManager())
		{
			$idq = bab_rp('idq');
			modifyQuestion($item, $idscat, $idq);
			$babBody->addItemMenu("questions", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenu("ModifyQ", bab_translate("Edit"),$GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$item."&idscat=".$idscat."&idq=".$idq);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=faq&idx=Delete&item=".$item."&idscat=".$idscat."&idq=".$idq);
		}
		break;

	case "ModifyC":
		$babBody->title = bab_translate("Modify subcategory");
		if( isUserManager())
		{
			modifySubCategory($item, $idscat, bab_rp('ids'));
			$babBody->addItemMenu("questions", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenu("ModifyC", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=faq&idx=ModifyC&item=".$item."&idscat=".$idscat);
		}
		break;

	case "Print":
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item) || bab_isAccessValid(BAB_FAQMANAGERS_GROUPS_TBL, $item))
		{

			FaqPrintContents($item);

			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			$babBody->addItemMenu("Print", bab_translate("Visualisation"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$item."&idscat=".$idscat);
			if( isUserManager()){
				$babBody->addItemMenu("questions", bab_translate("Management"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			}

		}
		break;

	default:
	case "Categories":
		$babBody->title = bab_translate("List of all faqs");
		if( listCategories() > 0 )
		{
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
		}
		break;
}

$babBody->setCurrentItemMenu($idx);
?>