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
	public function getClassSelector()
	{
		return '[class*=bab-articletopic-]';
	}
	
	protected function getTopicFromClasses(Array $classes)
	{
		foreach ($classes as $className) {
			$m = null;
			if (preg_match('/bab-articletopic-(\d+)/', $className, $m)) {
				return (int) $m[1];
			}
		}
		
		return null;
	}
	
	/**
	 * Get the list of actions
	 * @param array $classes all css classes found on the element
	 * @param bab_url $url Page url where the actions will be added
	 * @return Widget_Action[]
	 */
	public function getActions(Array $classes, bab_url $url)
	{
		
		$W = bab_Widgets();
		$id_topic = $this->getTopicFromClasses($classes);
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
	public function getClassSelector()
	{
		return '[class*=bab-article-]';
	}
	
	
	protected function getArticleFromClasses(Array $classes)
	{
		foreach ($classes as $className) {
			$m = null;
			if (preg_match('/bab-article-(\d+)/', $className, $m)) {
				return (int) $m[1];
			}
		}
	
		return null;
	}
	
	/**
	 * Get the list of actions
	 * @param array $classes all css classes found on the element
	 * @param bab_url $url Page url where the actions will be added
	 * @return Widget_Action[]
	 */
	public function getActions(Array $classes, bab_url $url)
	{
		require_once dirname(__FILE__).'/artapi.php';
		$W = bab_Widgets();
		$id_article = $this->getArticleFromClasses($classes);
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

