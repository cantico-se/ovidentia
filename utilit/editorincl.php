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
include_once $GLOBALS['babInstallPath']."utilit/eventincl.php";


function bab_editor_push(&$obj) {
	static $arr = array();
	if (NULL === $obj) {
		return $arr;
	}
	$arr[$obj->uid] = $obj;
}



/**
 * Editor interface
 */
class bab_contentEditor {

	/**
	 * @private
	 */
	var $uid;
	var $fieldname;
	var $content;
	var $format;
	var $parameters;
	var $editors;

	/**
	 * Create editor object 
	 * @param	string	$uid	unique string for editor instance, it describe de editor position
	 * @public
	 */
	function bab_contentEditor($uid) {
		$this->uid = $uid;
		
		// default fieldname to uid
		$this->fieldname = $uid;
		
		// default parameters
		$this->parameters = array(
			'height' => 500,
			'email' => false
		);
		
		$this->format = 'html'; // default format
		
		$this->editors = bab_editor_push($this);
	}
	
	/**
	 * Set html field name for the editor
	 * @param	string	$fieldname
	 * @public
	 */
	function setRequestFieldName($fieldname) {
		$this->fieldname = $fieldname;
	}
	
	/**
	 * set text to display in the editor or to convert in displayable html
	 * the input text is in format described by setFormat()
	 * @param	string	$content
	 * @public
	 */
	function setContent($content) {
		$this->content = $content;
	}
	
	/**
	 * set the format of the content when the content is loaded from database with setContent
	 * @param	string	$format
	 * @public
	 */
	function setFormat($format) {
		$this->format = $format;
	}
	
	
	/**
	 * get text to store in database after the form is submited
	 * from the fired event
	 * @see bab_eventEditorRequestToContent
	 *
	 * @return string
	 * @public
	 */
	function getContent() {
	
		if (!isset($this->content)) {
			$event = new bab_eventEditorRequestToContent();
			$this->setEventProperties($event);
			bab_fireEvent($event);
			$this->content = $event->getOutputContent();
			$this->format = $event->getOutputContentFormat();
		}
	
		return $this->content;
	}
	
	/**
	 * get the format of the text to store in database after the form is submited
	 * @return string
	 * @public
	 */
	function getFormat() {
	
		if (!isset($this->format)) {
			$event = new bab_eventEditorRequestToContent();
			$this->setEventProperties($event);
			bab_fireEvent($event);
			$this->content = $event->getOutputContent();
			$this->format = $event->getOutputContentFormat();
		}
	
		return $this->format;
	}
	
	/**
	 * define additionnal optional parameters for editor behavior
	 * @public
	 * @param	array	$arr
	 * @return array	all parameters
	 */
	function setParameters($arr) {
		$this->parameters = $arr + $this->parameters;
		return $this->parameters;
	}
	
	
	/**
	 * get the html
	 * Fire event bab_eventEditorContentToEditor to get the html form an addon
	 * @public
	 * @see bab_eventEditorContentToEditor
	 * @return string	html for editor
	 */
	function getEditor() {
		
		$event = new bab_eventEditorContentToEditor();
		$this->setEventProperties($event);
		bab_fireEvent($event);
		
		return $event->getOutputEditor();
	}
	
	/**
	 * get the displayable html of content
	 * @return string
	 */
	function getHtml() {
	
		
	
		$event = new bab_eventEditorContentToHtml();
		$this->setEventProperties($event);
		bab_fireEvent($event);
		
		return $event->getOutputHtml();
	}
	
	
	/**
	 * @private
	 */
	function setEventProperties(&$event) {
		$null = NULL;
		$this->editors = bab_editor_push($null);
		foreach($this as $property => $value) {
			$event->$property = $value;
		}
	}
}

/** 
 * All events for editor management
 */ 
class bab_eventEditor extends bab_event {

	/**
	 * @public
	 */
	var $uid;
	var $fieldname;
	var $content;
	var $format;
	var $parameters; 
}


/**
 * Event to get the editor
 */ 
class bab_eventEditorContentToEditor extends bab_eventEditor {
	
	/** 
	 * @private
	 */
	var $output_editor;
	
	/**
	 * set the html to display the editor with the content to modify
	 * action done by the registred function
	 * @public
	 */
	function setOutputEditor($html) {
		if (!isset($this->output_editor)) {
			$this->output_editor = $html;
		}
	}
	
	/**
	 * Used only by bab_contentEditor
	 * @public
	 * @see bab_contentEditor
	 * @return string
	 */
	function getOutputEditor() {
		if (!isset($this->output_editor)) {

			$content = isset($this->content) ? $this->content : '';
		
			bab_debug('No editor registered by bab_eventEditorContentToEditor::setOutputEditor(), fallback to simple textarea');
			return '<textarea id="'.bab_toHtml($this->uid).'" name="'.bab_toHtml($this->fieldname).'" cols="50" rows="10">'.bab_toHtml($content).'</textarea>';
		}
		return $this->output_editor;
	}
}


/**
 * Event to get the content submited by the editor
 */ 
class bab_eventEditorRequestToContent extends bab_eventEditor {
	/** 
	 * @private
	 */
	var $output_content;
	var $output_content_format;
	
	/**
	 * set the content to record in database
	 * action done by the registred function
	 * @public
	 */
	function setOutputContent($content, $format) {
		$this->output_content = $content;
		$this->output_content_format = $format;
	}
	
	/**
	 * Used only by bab_contentEditor
	 * @public
	 * @see bab_contentEditor
	 * @return string
	 */
	function getOutputContent() {
		if (!isset($this->output_content)) {
			bab_debug('No editor content input registered by bab_eventEditorRequestToContent::setOutputContent(), fallback to simple POST['.$this->fieldname.'] value');
			
			return bab_pp($this->fieldname);
		}
		return $this->output_content;
	}
	
	/**
	 * Used only by bab_contentEditor
	 * @public
	 * @see bab_contentEditor
	 * @return string
	 */
	function getOutputContentFormat() {
		if (!isset($this->output_content_format)) {
			bab_debug('No editor content format registered by bab_eventEditorRequestToContent::setOutputContent(), fallback to default : html');
			return 'html';
		}
		return $this->output_content_format;
	}
}

/**
 * Event to get the html from the stored data
 */ 
class bab_eventEditorContentToHtml extends bab_eventEditor {
	/** 
	 * @private
	 */
	var $output_html;
	
	/**
	 * set the displayabe html for the content
	 * action done by the registred function
	 * @public
	 */
	function setOutputHtml($html) {
		if (!isset($this->output_html)) {
			$this->output_html = $html;
		}
	}
	
	/**
	 * Used only by bab_contentEditor
	 * @public
	 * @see bab_contentEditor
	 * @return string
	 */
	function getOutputHtml() {
		if (!isset($this->output_html)) {
			bab_debug('No content registered by bab_eventEditorContentToHtml::setOutputHtml(), fallback to unmodified value');
			return $this->content;
		}
		return $this->output_html;
	}
}


/**
 * Event to retreive a list of editor instance
 */
class bab_eventEditors extends bab_event {

	/**
	 * @private
	 */
	var $editors = array();

	/**
	 * Register an editor instance
	 * @public
	 * @param	string	$uid
	 * @param	string	$name
	 * @param	string	$description
	 */
    function addEditor($uid, $name, $description) {
		$this->editors[$uid] = array(
			'name' 			=> $name,
			'description' 	=> $description
		);
    }
    
    
    /**
     * Get the list of registed editors
     * @public
     */
    function getEditors() {
    	return $this->editors;
    }
}




/**
 * Register core editor instances
 */
function bab_onEventEditors(&$event) {
	$event->addEditor('bab_note'					, bab_translate('Note')							, bab_translate('Note creation and modification'));
	$event->addEditor('bab_article_head'			, bab_translate('Article head')					, bab_translate('Article head'));
	$event->addEditor('bab_article_body'			, bab_translate('Article body')					, bab_translate('Article body'));
	$event->addEditor('bab_article_comment'			, bab_translate('Article comment')				, bab_translate('Article comment'));
	$event->addEditor('bab_calendar_event'			, bab_translate('Calendar event')				, bab_translate('Calendar event description'));
	$event->addEditor('bab_forum_post'				, bab_translate('Forum post')					, bab_translate('Forum thread or post'));
	$event->addEditor('bab_topic'					, bab_translate('Article topic')				, bab_translate('Article topic description'));
	$event->addEditor('bab_faq'						, bab_translate('Faq')							, bab_translate('Faq description'));
	$event->addEditor('bab_faq_response'			, bab_translate('Faq response')					, bab_translate('Faq response'));
	$event->addEditor('bab_section'					, bab_translate('Section')						, bab_translate('Section content'));
	$event->addEditor('bab_mail_signature'			, bab_translate('Mail signature')				, bab_translate('Mail signature'));
	$event->addEditor('bab_mail_message'			, bab_translate('Mail body')					, bab_translate('Mail body'));
	$event->addEditor('bab_disclaimer'				, bab_translate('Disclaimer')					, bab_translate('Disclaimer creation and modification'));
	$event->addEditor('bab_taskManagerDescription'	, bab_translate('Task manager description')		, bab_translate('Task manager description'));
}



/**
 * Event to register external functionalities
 */
class bab_eventEditorFunctions extends bab_event {

	/**
	 * @public
	 */
	var $uid;
	
	
	/**
	 * @private
	 */
	var $func = array();

	/**
	 * Register a function for editor
	 * 
	 * @param	string	$name
	 * @param	string	$description
	 * @param	string	$url
	 * @param	string	$iconpath
	 */
    public function addFunction($name, $description, $url, $iconpath) {
		$this->func[] = array(
			'name' 			=> $name,
			'description' 	=> $description,
			'url'			=> $url,
			'iconpath'		=> $iconpath
		);
    }
    
    
    /**
     * Get the list of registed functions
     * 
     */
    public function getFunctions() {
    	return $this->func;
    }
}


/**
 * Register core functions
 */
function bab_onEditorFunctions(&$event) {

	if ('bab_article_head' === $event->uid
	 || 'bab_article_body' === $event->uid
	 || 'bab_faq_response' === $event->uid) {

		$event->addFunction(
			bab_translate('Images'), 
			bab_translate('Insert image from ovidentia content image manager'), 
			'?tg=images',
			'skins/ovidentia/images/editor/ed_bab_image.gif'
		);
	
	}
	
	$event->addFunction(
		bab_translate('Files'), 
		bab_translate('Insert a link to file or folder in ovidentia files manager'), 
		'?tg=selector&idx=files&show_personal_directories=1&show_files=1&selectable_files=1&selectable_collective_directories=1&selectable_sub_directories=1&multi=1',
		'skins/ovidentia/images/editor/ed_bab_file.gif'
	);
	
	$event->addFunction(
		bab_translate('Articles'), 
		bab_translate('Insert a dynamic link to an article from ovidentia'), 
		'?tg=editorarticle&idx=brow',
		'skins/ovidentia/images/editor/ed_bab_articleid.gif'
	);
	
	$event->addFunction(
		bab_translate('Faqs'), 
		bab_translate('Insert a link to question/response from ovidentia FAQ'), 
		'?tg=editorfaq',
		'skins/ovidentia/images/editor/ed_bab_faqid.gif'
	);
	
	$event->addFunction(
		bab_translate('Ovml'), 
		bab_translate('Insert an ovml file'), 
		'?tg=editorovml',
		'skins/ovidentia/images/editor/ed_bab_ovml.gif'
	);
	
	if( !empty($GLOBALS['BAB_SESS_USER']) && bab_contactsAccess())
		{
			$event->addFunction(
				bab_translate('Contacts'), 
				bab_translate('Insert a link to a personnal contact'), 
				'?tg=editorcontdir',
				'skins/ovidentia/images/editor/ed_bab_contdir.gif'
			);
		}
		
	$event->addFunction(
		bab_translate('Directories'), 
		bab_translate('Insert link to a directory entry'), 
		'?tg=editorcontdir&idx=directory',
		'skins/ovidentia/images/editor/ed_bab_contdir.gif'
	);

}



/**
 * Create toolbar for low compatibility layer
 * @param	string	$editname
 * @param	string	$uid
 */
function bab_editor_text_toolbar($editname,$uid)
{
	if (!class_exists('text_toolbar'))
	{
	class text_toolbar
		{
		function text_toolbar($uid)
			{
			$this->uid = bab_toHtml($uid);
			$this->t_insert_ovidentia = bab_translate("Insert content links");
			}
		}
	}
	
	global $babBody;
	
	$babBody->addJavascriptFile($GLOBALS['babInstallPath'].'scripts/bab_dialog.js');
	$babBody->addJavascriptFile($GLOBALS['babInstallPath'].'scripts/text_toolbar.js');
	$babBody->addStyleSheet('text_toolbar.css');

	$tmp = new text_toolbar($uid);
	$tmp->editname = $editname;
	return bab_printTemplate($tmp,"uiutil.html", "babtexttoolbartemplate");
}

?>