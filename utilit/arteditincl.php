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
require_once dirname(__FILE__) . '/artdraft.class.php';



/**
 * Article Draft Editor
 */
class bab_ArticleDraftEditor {


	/**
	 * @var bab_ArtDraft
	 */
	private $draft = null;


	/**
	 * @var bool
	 */
	private $preview = false;


	/**
	 * @var string
	 */
	private $submitUrl = null;

	/**
	 * @var string
	 */
	private $cancelUrl = null;


	public function __construct(){


		$I = bab_functionality::get('Icons');
		$I->includeCss();

		$this->draft = new bab_ArtDraft;
		$this->cleanFiles();
	}



	/**
	 * Init draft from id draft
	 * @param int $idDraft
	 * @return bab_ArticleDraftEditor
	 */
	public function fromDraft($idDraft)
	{
		global $babBody;

		if (!bab_isDraftModifiable($idDraft))
		{
			$babBody->addError(bab_translate('Error, this draft is not modifiable'));
			$this->draft = null;
			return $this;
		}

		$this->draft->getFromIdDraft($idDraft);
		return $this;
	}

	/**
	 * Init draft from id article
	 * @param	int	$idArticle
	 * @return bab_ArticleDraftEditor
	 */
	public function fromArticle($idArticle)
	{
		global $babBody;

		try {
			$this->draft->getFromIdArticle($idArticle);
		}
		catch(ErrorException $e)
		{
			$babBody->addError($e->getMessage());
			$this->draft = null;
		}

		return $this;
	}


	/**
	 * Init draft from id topic
	 * @param int $idTopic
	 * @return bab_ArticleDraftEditor
	 */
	public function fromTopic($idTopic)
	{
		global $babBody;

		try {
			$this->draft->createInTopic($idTopic);
		}
		catch(ErrorException $e)
		{
			$babBody->addError($e->getMessage());
			$this->draft = null;
		}

		return $this;
	}



	/**
	 *
	 * @param bool $preview
	 * @return bab_ArticleDraftEditor
	 */
	public function preview($preview = true)
	{
		$this->preview = $preview;
		return $this;
	}



	/**
	 * Set url to go to after submit or cancel
	 * @param	bab_url $url
	 * @return bab_ArticleDraftEditor
	 */
	public function setBackUrl(bab_url $url)
	{
		$this->submitUrl = $url->toString();
		$this->cancelUrl = $url->toString();
	}


	/**
	 * Remove temorary files used in editor
	 * @return unknown_type
	 */
	private function cleanFiles()
	{
		if (!empty($_POST))
		{
			return;
		}


		$W = bab_Widgets();

		try {
			$W->FilePicker()->setName('articleFiles')->getFolder()->deleteDir();
		} catch(bab_FolderAccessRightsException $e) {
			// ignore, the folder does not exists
		}

		try {
			$W->FilePicker()->setName('articlePicture')->getFolder()->deleteDir();
		} catch(bab_FolderAccessRightsException $e) {
			// ignore, the folder does not exists
		}
	}
	
	public function getTopicTemplate()
	{
		$values = array('head' => '', 'body' => '');
	
		if (!bab_gp('topics',false))
		{
			return false;
		}
	
		global $babDB;
	
		$res = $babDB->db_query("select article_tmpl from bab_topics  where id=".$babDB->quote(bab_gp('topics',false)));
		if( !$res || $babDB->db_num_rows($res) !== 1 ) {
			return false;
		}
	
	
		$topic = $babDB->db_fetch_array($res);
		$template = $topic['article_tmpl'];
	
		if (empty($template))
		{
			return $values;
		}
	
		return bab_getTopicTemplate($template, 'html', 'html');
	}
	
	
	public static function suggestTag()
	{
		global $babDB;
		$W = bab_Widgets();
		
		$tags = $W->SuggestLineEdit('bab_artedit_suggesttag');
		if ($keyword = $tags->getSearchKeyword())
		{
			// search for keyword
		
			$res = $babDB->db_query("SELECT tag_name FROM bab_tags WHERE tag_name LIKE '".$babDB->db_escape_like($keyword)."%'");
			while ($arr = $babDB->db_fetch_assoc($res))
			{
				$tags->addSuggestion($arr['tag_name'], $arr['tag_name']);
			}
		
			$tags->sendSuggestions();
		}
		
		die();
	}


	/**
	 * Display HTML
	 * @return unknown_type
	 */
	public function display()
	{
		if (null === $this->draft)
		{
			// a null draft is a failed initilialisation from one of the from... methods
			return;
		}

		include_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
		global $babBody, $babDB;

		$W = bab_Widgets();
		$W->includeCss();
		
		



		$babBody->setTitle(bab_translate('Article publication'));

		$page = $W->BabPage();
		$page->addJavascriptFile($GLOBALS['babScriptPath'].'bab_article.js');
		$page->addStyleSheet($GLOBALS['babInstallPath'].'styles/artedit.css');


		if($this->preview){
			$page->addItem(
				$W->Html('
					<div id="dialog" style="visibilty: hidden">
						<iframe src="?tg=artedit&idx=preview&idart='.$this->draft->getId().'" width="100%" height="100%">
							<p>Your browser does not support iframes.</p>
						</iframe>
					</div>')
			);
		}

		$LeftFrame = $W->VBoxLayout()->setVerticalSpacing(10,'px');


		$topicList = bab_getArticleTopicsAsTextTree(0, false, BAB_TOPICSSUB_GROUPS_TBL);

		$topic = $W->Select('bab-article-topic');

		foreach($topicList as $topcat){

			$topcat['name'] = bab_abbr($topcat['name'], BAB_ABBR_FULL_WORDS, 50);

			if($topcat['category']){
				$topic->addOption($topic->SelectOption('cat-'.$topcat['id_object'], $topcat['name'])->disable()->addClass('category'));
			} else {
				$topic->addOption($topic->SelectOption($topcat['id_object'], $topcat['name']));
			}
		}

		if ($this->draft->id_topic)
		{
			$topic->addOption($topic->SelectOption($this->draft->id_topic, bab_getTopicTitle($this->draft->id_topic)));
		}


		$LeftFrame->addItem(
			$W->Section(
				$tempLab = $W->Label(bab_translate('Title')),
				$W->Frame()->addItem(
					$W->LineEdit('title')->setAssociatedLabel($tempLab)->setMandatory(true, bab_translate('The title is mandatory'))->setName('title')
				)
			)->setFoldable(false)
		);

		$tpl = $this->getTopicTemplate();

		$headEditor = new bab_contentEditor('bab_article_head');
		$headEditor->setRequestFieldName('head');
		$headEditor->setContent($this->draft->head);
		if (isset($_POST['head']))
		{
			$headEditor->setContent($_POST['head']);
		}elseif($tpl && !$this->draft->head){
			$headEditor->setContent($tpl['head']);
		}
		$headEditor->setParameters(array('height' => '200'));

		$LeftFrame->addItem(
			$W->Section(
				$tempLab = $W->Label(bab_translate('Introduction'))->addClass('widget-label-mandatory'),
				$W->Frame('intro')->addItem(
					$W->Html($headEditor->getEditor())
				)
			)->setFoldable(true)
		);


		$bodyEditor = new bab_contentEditor('bab_article_body');
		$bodyEditor->setRequestFieldName('body');
		$bodyEditor->setContent($this->draft->body);
		if (isset($_POST['body']))
		{
			$bodyEditor->setContent($_POST['body']);
		}elseif($tpl){
			$headEditor->setContent($tpl['body']);
		}
		$bodyEditor->setParameters(array('height' => '300'));

		$LeftFrame->addItem(
			$body = $W->Section(
				bab_translate('Body'),
				$W->Frame()->addItem(
					$W->Html($bodyEditor->getEditor())
				)
			)->setFoldable(true)
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

		$LeftFrame->addItem(

				$W->FlowLayout()
					->addItem(
						bab_labelStr(
							bab_translate("Submission date"),
							$W->HBoxItems(
								$W->DatePicker()->setName('date_submission'),
								$time_submission = $W->Select()->setName('time_submission')->setValue('00:00:00')->setOptions($timeArray)
							)->setHorizontalSpacing(5, 'px')
						)
					)->addItem(
						bab_labelStr(
							bab_translate("Publication date"),
							$W->HBoxItems(
								$W->DatePicker()->setName('date_publication')->disable(),
								$time_publication = $W->Select()->setName('time_publication')->setValue('00:00:00')->setOptions($timeArray)
							)->setHorizontalSpacing(5, 'px')
						)
					)->addItem(
						bab_labelStr(
							bab_translate("Archiving date"),
							$W->HBoxItems(
								$W->DatePicker()->setName('date_archiving')->disable(),
								$time_archiving = $W->Select()->setName('time_archiving')->setValue('00:00:00')->setOptions($timeArray)
							)->setHorizontalSpacing(5, 'px')
						)
					)->setHorizontalSpacing(1,'em')
		);


		$LeftFrame->addItem(
			$attachments = $W->Section(
				bab_translate('Files attachments'),
				$W->Frame()
					->addItem($articleFiles = $W->FilePicker()->setTitle(bab_translate('Add a file'))->setName('articleFiles')->disable()->hideFiles())
					->addItem($fileList = $W->Frame('bab_article_file_list')->addClass('widget-sortable')),
				4,
				'bab_article_attachments'
			)->setFoldable(true, true)
		);

		/** @var $articleFiles Widget_FilePicker */

		if ($articleFiles->getValue())
		{
			$attachments->setFoldable(true, false);
		}

		$LeftFrame->addItem(
				$W->Frame()->addItem(
					bab_labelStr(
						bab_translate("Keywords"),
						$W->SuggestLineEdit('bab_artedit_suggesttag')->setSize(35)->setName('tags')->disable()->setMultiple(',')->setMinChars(1)
							->setSuggestAction($W->Action()->fromUrl($GLOBALS['babUrlScript']."?tg=artedit&idx=suggesttag"))
					)
				)
		);

		

		$LeftFrame->addItem(
				$W->Frame()
					->addItem($articlePicture = $W->ImagePicker()->oneFileMode(true)
						->setTitle(bab_translate('Set the article picture'))
						->setName('articlePicture')
						->disable()
						->addClass('bab-article-picture')
					)
		);

		$LeftFrame->addItem(
			$W->HBoxItems(
				$tempCheck = $W->CheckBox()->setName('hpage_public')->setUncheckedValue('N')->setCheckedValue('Y')->disable(),
				$W->Label(bab_translate("Propose for public homepage"))->setAssociatedWidget($tempCheck)
			)->setVerticalSpacing(5, 'px')->setVerticalAlign('middle')
		);
		$LeftFrame->addItem(
			$W->HBoxItems(
				$tempCheck = $W->CheckBox()->setName('hpage_private')->setUncheckedValue('N')->setCheckedValue('Y')->disable(),
				$W->Label(bab_translate("Propose for private homepage"))->setAssociatedWidget($tempCheck)
			)->setVerticalSpacing(5, 'px')->setVerticalAlign('middle')
		);
		$LeftFrame->addItem(
			$W->HBoxItems(
				$tempCheck = $W->CheckBox()->setName('notify_members')->setUncheckedValue('N')->setCheckedValue('Y')->disable(),
				$W->Label(bab_translate("Notify users when the article is published"))->setAssociatedWidget($tempCheck)
			)->setVerticalSpacing(5, 'px')->setVerticalAlign('middle')
		);

		$groups = bab_labelStr(
			bab_translate('Groups'),
			$multigroups = $W->MultiField()->setName('groups')
		);


		$operator = bab_labelStr(
			bab_translate('With operator'),
			$W->Select()
				->setName('operator')
				->addOption(',',bab_translate('Or'))
				->addOption('&',bab_translate('And'))
		);

		$LeftFrame->addItem(
			$W->Frame()
				->addItem(
					bab_labelStr(
						bab_translate('Access restriction'),
						$restriction = $W->Select()->disable()
							->setName('restriction')
							->addOption('', bab_translate('No restrictions'))
							->addOption('1', bab_translate('Groups'))
							->setAssociatedDisplayable($groups, array(1))
							->setAssociatedDisplayable($operator, array(1))
					)
				)
				->addItem($groups)
				->addItem($operator)
				->addClass('bab-article-restriction')
		);

		$LeftFrame->addItem(
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


		if($this->draft->id_article){
			$LeftFrame->addItem(
				$update_datemodif = $W->Section(
					bab_translate('Reasons for changes'),
					$W->Frame()
						->addItem($W->TextEdit()->setName('modification_comment')->addClass('modification_comment'))
						->additem($W->HBoxItems($W->Checkbox()->setName('update_datemodif')->setCheckedValue('N')->setUncheckedValue('Y'), $W->Label(bab_translate("Don't update article modification date"))))
				)->setFoldable(true)
			);
		}


		/*@var $articlePicture Widget_FilePicker */
		/*@var $articleFiles Widget_FilePicker */

		$articlePicture->setEncodingMethod(null);
		$articleFiles->setEncodingMethod(null)->onUpload('filesAttachments', 'window.babArticle');

		$imgsize = (int) $babBody->babsite['imgsize'];
		if($imgsize > 0)
		{
			$articlePicture->setMaxSize($imgsize * 1024);
		}


		if ($this->draft->getId() && empty($_POST))
		{
			// load files from draft
			$this->draft->loadTempAttachments($articleFiles);


			// load picture from draft
			$this->draft->loadTempPicture($articlePicture);


		}


		// Set values in from


		$values = $this->draft->getValues();

		if($values['id_topic'] != ""){
			$topicIdFolded = true;
			$topicIdOrder = 0;
			$currentTopic = viewCategoriesHierarchy_txt($values['id_topic']);
		}else{
			$currentTopic =  bab_translate('Current topic') . ': ' . bab_translate('None');
			$topicIdFolded = false;
			$topicIdOrder = 0;
		}

		$LeftFrame->addItem(
			$W->Frame()->addItem(
				$W->Section(
					$W->Html(bab_translate('Article topic') . ' (' . $currentTopic . ')'),
					$W->FlowItems($topic->setName('id_topic')),
					3
				)->setFoldable(true, $topicIdFolded)
			),
			0
		);

		$LeftFrame->addItem(
			$W->Frame()->addItem(
				$W->Label(bab_translate('The topic is restricted by an approbation, the article will not be visible imediately'))
			)
			->addClass('bab-article-approbation')
		);

		$LeftFrame->addItem(
			$W->HBoxItems(
				$W->SubmitButton()->setLabel(bab_translate('Cancel'))->setName('cancel')->setConfirmationMessage(bab_translate('Do you really want to delete the draft?')),
				$W->SubmitButton()->validate(true)->setLabel(bab_translate('Save a draft'))->setName('draft'),
				$W->SubmitButton()->validate(true)->setLabel(bab_translate('Preview'))->setName('see'),
				$W->SubmitButton()->validate(true)->setLabel(bab_translate('Submit'))->setName('submit')
			)->setHorizontalSpacing(5,'px')
		);

		$globalFrame = $LeftFrame->setHorizontalSpacing(30, 'px')->setId('global-article-page');

		$values['tags'] = implode(', ', $this->draft->getTags());
		$values['operator'] = $this->draft->getOperator();


		if(empty($values['body']) && empty($_POST['body'])){
			$body->setFoldable(true, true);
		}

		if (isset($update_datemodif) && empty($values['modification_comment'])) {
			$update_datemodif->setFoldable(true, true);
		}

		$restrictions = $this->draft->getRestrictions();
		if (empty($restrictions))
		{
			// options / values will be set with javascript
			$multigroups->addItem($W->Select()->setName('0'));

		} else {

			$values['restriction'] = 1;

			// options / values are set server side and lost if topic is changed
			$i = 0;
			foreach($restrictions as $id_group)
			{
				$options = $this->draft->getRestrictionsOptions();
				if (!isset($options[$id_group]))
				{
					continue;
				}

				$multigroups->addItem($W->Select()->setName((string) $i)->setOptions($options)->setValue($id_group));
				$i++;
			}

			if ($this->draft->id_topic)
			{
				$restriction->addClass('bab-article-restriction-topic-'.$this->draft->id_topic);
			}
		}



		if(isset($values['date_submission'])){
			$date_submission = explode(' ', $values['date_submission']);
			if(isset($date_submission[0]) && $date_submission[0] == '0000-00-00'){
				unset($values['date_submission']);
			}
			if(isset($date_submission[1])){
				$time_submission->setValue($date_submission[1]);
			}
		}

		if(isset($values['date_publication'])){
			$date_publication = explode(' ', $values['date_publication']);
			if(isset($date_publication[0]) && $date_publication[0] == '0000-00-00'){
				unset($values['date_publication']);
			}
			if(isset($date_publication[1])){
				$time_publication->setValue($date_publication[1]);
			}
		}

		if(isset($values['date_archiving'])){
			$date_archiving = explode(' ', $values['date_archiving']);
			if(isset($date_archiving[0]) && $date_archiving[0] == '0000-00-00'){
				unset($values['date_archiving']);
			}
			if(isset($date_archiving[1])){
				$time_archiving->setValue($date_archiving[1]);
			}
		}


		$FormArticle = $W->Form('article-form',$globalFrame)
			->setValues($values)
			->setValues($_POST)
			->setHiddenValue('tg', 'artedit')
			->setHiddenValue('idx', 'save')
			->setHiddenValue('iddraft', $this->draft->getId())
			->setHiddenValue('ajaxpath', $GLOBALS['babUrlScript'])
			->setHiddenValue('submitUrl', bab_pp('submitUrl', $this->submitUrl))
			->setHiddenValue('cancelUrl', bab_pp('cancelUrl', $this->cancelUrl))
			->setHiddenValue('babpopup', false);


		$page->addItem($FormArticle);

		$page->displayHtml();
	}
}