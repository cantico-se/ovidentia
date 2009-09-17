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




function htmlarea_onContentToEditor(&$event) {

	if (!empty($event->content) && 'html' !== $event->format) {
		return;
	}

	if( !class_exists('babEditorCls'))
		{
		class babEditorCls
			{
			var $editname;
			var $formname;
			var $contentval;

			function babEditorCls($content, $editname, $heightpx,$uid)
				{
				global $babDB;
				$this->heightpx = $heightpx;
				$this->uid = $uid;
				$this->editname = $editname;
				if (!list($use_editor,$this->filter_html) = $babDB->db_fetch_array($babDB->db_query("SELECT use_editor, filter_html FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$babDB->db_escape_string($GLOBALS['babBody']->babsite['id'])."'")))
					{
					$use_editor = 1;
					$this->filter_html = 0;
					}

				$this->t_filter_html = bab_translate("In this configuration, some html tags may be removed for security reasons");
				$this->text_toolbar = bab_editor_text_toolbar($editname,$this->uid);
				// do not load script for ie < 5.5 to avoid javascript parsing errors

				preg_match("/MSIE\s+([\d|\.]*?);/", $_SERVER['HTTP_USER_AGENT'], $matches);
				$this->loadscripts = $use_editor && (!isset($matches[1]) || ($matches[1] >= 5.5));
				

				if( empty($content))
					{
					$this->contentval = "";
					}
				else
					{
					$this->contentval = bab_toHtml($content);
					}
				}	
			}
		}
		
	$temp = new babEditorCls(
		$event->content, 
		$event->fieldname, 
		$event->parameters['height'],
		$event->uid
	);

	
	static $n = 0;
	$n++;
	$temp->last = count($event->editors) == $n;
	$temp->first = 1 == $n;
	
	$arr = array();
	foreach($event->editors as $editor) {
		$arr[] = "'".$editor->uid."'";
	}
	
	$temp->listeditname = implode(',',$arr);

	$event->setOutputEditor(
		bab_printTemplate($temp,"uiutil.html", "babeditortemplate")
	);
}



function htmlarea_onRequestToContent(&$event) {
	
	
	if ('html' !== bab_pp($event->fieldname.'_format')) {
		return;
	}
	
	$content = bab_pp($event->fieldname);
	bab_editor_record($content);
	$event->setOutputContent($content, 'html');
}


function htmlarea_onContentToHtml(&$event) {

	if ('html' !== $event->format) {
		return;
	}

	$event->setOutputHtml(bab_toHtml($event->content, BAB_HTML_REPLACE));
}


?>
