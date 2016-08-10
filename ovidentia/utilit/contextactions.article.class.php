<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */


class Func_ContextActions_ArticleTopic extends Func_ContextActions
{
	
	public function getDescription()
	{
		return bab_translate('Match a topic');
	}

	/**
	 * Get a pattern or string to match a CSS class
	 * @return string
	 */
	public function getClassPattern()
	{
		return 'bab-articletopic-(\d+)';
	}
	
	/**
	 *
	 * @return Widget_Action[]
	 */
	public function getActions()
	{
		$W = bab_Widgets();
		$id_topic = (int) $this->matches[1];
		$actions = array();
		
		if (bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $id_topic)) {
			$actions[] = $W->Action()
				->setMethod('topman', 'Articles', array('item' => $id_topic))
				->setTitle(bab_translate('Manage topic'))
				->setIcon(Func_Icons::ACTIONS_DOCUMENT_PROPERTIES);
		}
		
		if (bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $id_topic)) {
			$actions[] = $W->Action()
				->setMethod('articles', 'Submit', array('topics' => $id_topic))
				->setTitle(bab_translate('Create article'))
				->setIcon(Func_Icons::ACTIONS_ARTICLE_NEW);
		}
		
		return $actions;
	}
}


class Func_ContextActions_Article extends Func_ContextActions
{
	public function getDescription()
	{
		return bab_translate('Match an article');
	}
	
	/**
	 * Get a pattern or string to match a CSS class
	 * @return string
	 */
	public function getClassPattern()
	{
		return 'bab-article-(\d+)';
	}
	
	/**
	 * 
	 * @return Widget_Action[]
	 */
	public function getActions()
	{
		$W = bab_Widgets();
		$id_article = (int) $this->matches[1];
		$article = bab_getArticleArray($id_article);
		
		$actions = array();
		
		if (bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $article['id_topic'])) {
			$actions[] = $W->Action()
			->setMethod('articles', 'Modify', array('article' => $id_article))
			->setTitle(bab_translate('Edit article'))
			->setIcon(Func_Icons::ACTIONS_DOCUMENT_EDIT);
		}
		
		
		return $actions;
	}
}

