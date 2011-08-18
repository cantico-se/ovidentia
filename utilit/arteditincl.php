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

require_once 'base.php';
require_once dirname(__FILE__) . '/artapi.php';




/**
 * 
 * New function to edit articles
 * @param int $idArticle article id ord draft id
 */
function bab_editArticleOrDraft($idArticle = '', $idDraft = '', $arrPreview = false){

	$W = bab_Widgets();
	$W->includeCss();

	$I = bab_functionality::get('Icons');
	$I->includeCss();
	global $babBody, $babDB;
	
	
	if (!$idDraft && $idArticle)
	{
		// create draft from article
		try {
			$idDraft = bab_newArticleDraft(0, $idArticle);
		} catch(ErrorException $e)
		{
			$babBody->addError($e->getMessage());
			return;
		}
		$idArticle = '';
	}
	
	if ($idDraft)
	{
	
		$res = $babDB->db_query('Select * from ' . BAB_ART_DRAFTS_TBL . ' where id = ' . $babDB->quote($idDraft));
		$draft = $babDB->db_fetch_assoc($res);
		if(!$draft){
			throw new ErrorException('This draft does not exists');
		}else{
			$draft['bab_article_head'] = $draft['head'];
			$draft['bab_article_body'] = $draft['body'];
			$draft['topicid'] = $draft['id_topic'];
		}
	} else {
		// new draft, not saved
		$draft = array('bab_article_head' => '', 'bab_article_body' => '');
	}

	$babBody->setTitle(bab_translate('Article publication'));
	
	$page = $W->BabPage();
	$page->addJavascriptFile($GLOBALS['babScriptPath'].'bab_article.js');
	$page->addStyleSheet($GLOBALS['babInstallPath'].'styles/artedit.css');
	
	
	if($arrPreview){
		$page->addItem(
			$W->Html('
				<div id="dialog" style="visibilty: hidden">
					<iframe src="?tg=artedit&idx=newpreview" width="100%" height="100%">
						<p>Your browser does not support iframes.</p>
					</iframe>
				</div>')
		);
	}

	$LeftFrame = $W->VBoxLayout()->setVerticalSpacing(10,'px');
	
	$LeftFrame->addItem($W->Html('
		<style type="text/css">
			#title, #intro, #corps, #modify{
				width: 100%;
			}
			#title-label{
				font-weight: bold;
			}
			#textEdit_modify{
				width: 100%;
			}
			#textEdit_modify{
				border: 2px solid #aaa !important; 
			}
			#global-article-page{
				margin: 20px;
				margin-top: 0;
			}
			.nowrap{
				white-space: nowrap;
			}
		</style>
	'));
	
	$topicList = bab_getArticleTopicsAsTextTree(0);
	
	
	
	
	$accessibleTopic = array('' => '');
	foreach($topicList as $topic){
		if( $idArticle != ''){
			if(!$topic['category'] && bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $topic['id_object'])){
				$accessibleTopic[$topic['id_object']] = $topic['name'];
			}
		}else{
			if(!$topic['category'] && bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topic['id_object'])){
				$accessibleTopic[$topic['id_object']] = $topic['name'];
			}
		}
	}
	
	if (1 === count($accessibleTopic))
	{
		$babBody->addError(bab_translate('No accessible topic'));
		return;
	}
	
	$LeftFrame->addItem(
		$W->Section(
			$tempLab = $W->Label('Titre'),
			$W->Frame()->addItem(
				$W->LineEdit('title')->setAssociatedLabel($tempLab)->setMandatory(true, bab_translate('The title is mandatory'))->setName('title')
			)
		)->setFoldable(false)
	);
	
	$LeftFrame->addItem(
		$W->Section(
			$tempLab = $W->Label(bab_translate('Introduction')),
			$W->Frame('intro')->addItem(
				$introEditor = $W->BabHtmlEdit('bab_article_head')->setName('head')->setAssociatedLabel($tempLab)->setMandatory(true, bab_translate('The body is mandatory'))
			)
		)->setFoldable(true)
	);
	
	$LeftFrame->addItem(
		$body = $W->Section(
			bab_translate('Body'),
			$W->Frame()->addItem(
				$bodyEditor = $W->BabHtmlEdit('bab_article_body')->setName('body')
			)
		)->setFoldable(true)
	);
	
	
	$LeftFrame->addItem(
		$attachments = $W->Section(
			bab_translate('Files attachments'),
			$W->Frame()
				->addItem($articleFiles = $W->FilePicker()->setTitle(bab_translate('Add a file'))->setName('articleFiles')->disable()->hideFiles())
				->addItem($fileList = $W->Frame('bab_article_file_list')->addClass('widget-sortable')),
			3,
			'bab_article_attachments'
		)->setFoldable(true, true)
	);
	
	
	if(isset($draft['bab_article_body']) && empty($draft['bab_article_body'])){
		$body->setFoldable(true, true);
	}
	
	if($idArticle != ""){
		$LeftFrame->addItem(
			$W->Section(
				bab_translate('Reasons for changes'),
				$W->Frame('modify')->addItem(
					$W->TextEdit('textEdit_modify')->setName('modify')
				)
			)->setFoldable(true, true)
		);
	}
	
	$LeftFrame->addItem(
		$W->HBoxItems(
			$W->SubmitButton()->setLabel(bab_translate('Cancel'))->setName('cancel'),
			$W->SubmitButton()->validate(true)->setLabel(bab_translate('Save a draft'))->setName('draft'),
			$W->SubmitButton()->validate(true)->setLabel(bab_translate('Preview'))->setName('see'),
			$W->SubmitButton()->validate(true)->setLabel(bab_translate('Submit'))->setName('submit')
		)->setHorizontalSpacing(5,'px')
	);

	$RightFrame = $W->VBoxLayout()->setVerticalSpacing(10,'px');
	
	$RightFrame->addItem(
		bab_labelStr(
			bab_translate('Article topic'),
			$W->Select('bab-article-topic')
				->setOptions($accessibleTopic)
				->setName('topicid')
		)
	);
	
	$timeArray = array();
	for($i=0; $i < 1440; $i=$i+5){
		$hour = floor($i/60);
		if(strlen($hour) == 1){
			$hour = '0'.$hour;
		}
		$minute = $i%60;
		if(strlen($minute) == 1){
			$minute = '0'.$minute;
		}
		$timeArray[$hour.':'.$minute.':00'] = $hour.':'.$minute;
	}
	
	$RightFrame->addItem(
		bab_labelStr(
			bab_translate("Submission date"),
			$W->HBoxItems(
				$W->DatePicker()->setName('date_submission'),
				$time_submission = $W->Select()->setName('time_submission')->setValue('00:00:00')->setOptions($timeArray)
			)->setHorizontalSpacing(5, 'px')
		)
	);
	$RightFrame->addItem(
		bab_labelStr(
			bab_translate("Publication date"),
			$W->HBoxItems(
				$W->DatePicker()->setName('date_publication')->setValue(date('d-m-Y'))->disable(),
				$time_publication = $W->Select()->setName('time_publication')->setValue('00:00:00')->setOptions($timeArray)
			)->setHorizontalSpacing(5, 'px')
		)
	);
	$RightFrame->addItem(
		bab_labelStr(
			bab_translate("Archiving date"),
			$W->HBoxItems(
				$W->DatePicker()->setName('date_archiving')->disable(),
				$time_archiving = $W->Select()->setName('time_archiving')->setValue('00:00:00')->setOptions($timeArray)
			)->setHorizontalSpacing(5, 'px')
		)
	);
	$RightFrame->addItem(
		$W->HBoxItems(
			$tempCheck = $W->CheckBox()->setName('hpage_public')->setUncheckedValue('N')->setCheckedValue('Y')->disable(),
			$W->Label(bab_translate("Propose for public homepage"))->setAssociatedWidget($tempCheck)
		)->setVerticalSpacing(5, 'px')->setVerticalAlign('middle')
	);
	$RightFrame->addItem(
		$W->HBoxItems(
			$tempCheck = $W->CheckBox()->setName('hpage_private')->setUncheckedValue('N')->setCheckedValue('Y')->disable(),
			$W->Label(bab_translate("Propose for private homepage"))->setAssociatedWidget($tempCheck)
		)->setVerticalSpacing(5, 'px')->setVerticalAlign('middle')
	);
	$RightFrame->addItem(
		$W->HBoxItems(
			$tempCheck = $W->CheckBox()->setName('notify_members')->setUncheckedValue('N')->setCheckedValue('Y')->disable(),
			$W->Label(bab_translate("Notify users when the article is published"))->setAssociatedWidget($tempCheck)
		)->setVerticalSpacing(5, 'px')->setVerticalAlign('middle')
	);
	
	$RightFrame->addItem(
		bab_labelStr(
			bab_translate("Article language"),
			$lang = $W->Select()
				->setValue('fr')
				->setName('lang')
				->addOption('*','*')
		)
	);
	
	$languages = bab_getAvailableLanguages();
	foreach($languages as $l)
	{
		$lang->addOption($l,$l);
	}
	
	
	$RightFrame->addItem(
		bab_labelStr(
			bab_translate("Keywords"),
			$W->LineEdit()->setName('tags')->disable()
		)
	);
	
	$RightFrame->addItem(
		bab_labelStr(
			bab_translate('Access restriction'),
			$W->Select()->disable()
				->setName('restriction')
				->addOption('', bab_translate('No restrictions'))
				->addOption('1', bab_translate('Groups'))
		)
	);
	
	$RightFrame->addItem(
		bab_labelStr(
			bab_translate('With operator'),
			$W->Select()->disable()
				->setName('operator')
				->addOption(',',bab_translate('Or'))
				->addOption('&',bab_translate('And'))
		)
	);
	
	$RightFrame->addItem(
		$articlePicture = $W->FilePicker()->oneFileMode(true)->setTitle(bab_translate('Add a picture'))->setName('articlePicture')->disable()
	);

	
	/*@var $articlePicture Widget_FilePicker */
	/*@var $articleFiles Widget_FilePicker */
	
	$articlePicture->setEncodingMethod(null);
	$articleFiles->setEncodingMethod(null)->onUpload('filesAttachments', 'window.babArticle');
	
	
	$tmpPath = $articleFiles->getFolder();
	$tmpPath->createDir();
	
	if ($idDraft)
	{
		// load files from draft
		
		
		$draftPath = new bab_path($GLOBALS['babUploadPath'], 'drafts');
		// les fichiers actuel sont enregistres dans le repertoire draft avec id,fichier
		
		$res = $babDB->db_query("SELECT name, description, ordering FROM bab_art_drafts_files WHERE id_draft=".$babDB->quote($idDraft));
		while ($arr = $babDB->db_fetch_assoc($res))
		{
			$targetPath = clone $tmpPath;
			$targetPath->push($arr['name']);
			$filePath = clone $draftPath;
			$filePath->push($idDraft.','.$arr['name']);
			copy($filePath->toString(), $targetPath->toString());
			
			$_SESSION['bab_articleTempAttachments'][$arr['name']] = array(
				'description' 	=> $arr['description'],
				'ordering'		=> $arr['ordering']
			);
		}
		
		// TODO emplacement du fichier image ?
		// $articlePicture
		
		
	}
	
	$globalFrame = $W->HboxItems(
		$LeftFrame->setSizePolicy(Widget_SizePolicy::MAXIMUM),
		$RightFrame->setSizePolicy(Widget_SizePolicy::MINIMUM)
	)->setHorizontalSpacing(30, 'px')->setId('global-article-page');
	
	if(!empty($draft) && isset($draft['date_submission'])){
		$date_submission = explode(' ', $draft['date_submission']);
		if(isset($date_submission[0]) && $date_submission[0] == '0000-00-00'){
			unset($draft['date_submission']);
		}
		if(isset($date_submission[1])){
			$time_submission->setValue($date_submission[1]);
		}
		
	}
	
	if(!empty($draft) && isset($draft['date_publication'])){
		$date_publication = explode(' ', $draft['date_publication']);
		if(isset($date_publication[0]) && $date_publication[0] == '0000-00-00'){
			unset($draft['date_publication']);
		}
		if(isset($date_publication[1])){
			$time_publication->setValue($date_publication[1]);
		}
		
	}
	
	if(!empty($draft) && isset($draft['date_archiving'])){
		$date_archiving = explode(' ', $draft['date_archiving']);
		if(isset($date_archiving[0]) && $date_archiving[0] == '0000-00-00'){
			unset($draft['date_archiving']);
		}
		if(isset($date_archiving[1])){
			$time_archiving->setValue($date_archiving[1]);
		}
		
	}
	
	$FormArticle = $W->Form('article-form',$globalFrame)
		->setValues($draft)
		->setValues($_POST)
		->setHiddenValue('tg', 'artedit')
		->setHiddenValue('idx', 'newsave')
		->setHiddenValue('idart', $idArticle)
		->setHiddenValue('iddraft', $idDraft)
		->setHiddenValue('ajaxpath', $GLOBALS['babUrlScript']);
	
	if(isset($_SESSION['bab_article_draft_preview'])){
		$introEditor->setValue($_SESSION['bab_article_draft_preview']['1']);
		$bodyEditor->setValue($_SESSION['bab_article_draft_preview']['2']);
	}

	$page->addItem($FormArticle);

	$page->displayHtml();
}
