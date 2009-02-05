<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
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
include_once 'base.php';

require_once $GLOBALS['babInstallPath'] . 'utilit/uiutil.php';
require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';


function getAttributesFromRp(array $params, &$attributes, &$urlAttributes)
{
	$attributes = 0;
	$urlAttributes = '';

	foreach ($params as $paramName => $attributeValue) {
		if (bab_rp($paramName, false)) {
			$attributes |= $attributeValue;
			$urlAttributes .= '&' . $paramName . '=1';
		}
	}
} 


/**
 * Displays an article selection popup.
 */
function selectArticles()
{
	global $babBody;

	$params = array(
		'show_categories' => bab_ArticleTreeView::SHOW_CATEGORIES,
		'show_topics' => bab_ArticleTreeView::SHOW_TOPICS,
		'show_articles' => bab_ArticleTreeView::SHOW_ARTICLES,
		'selectable_categories' => bab_ArticleTreeView::SELECTABLE_CATEGORIES,
		'selectable_topics' => bab_ArticleTreeView::SELECTABLE_TOPICS,
		'selectable_articles' => bab_ArticleTreeView::SELECTABLE_ARTICLES,
		'hide_delegations' => bab_ArticleTreeView::SHOW_ONLY_ADMINISTERED_DELEGATION, // DEPRECATED
		'show_only_administered_delegation' => bab_ArticleTreeView::SHOW_ONLY_ADMINISTERED_DELEGATION,
		'multi' => bab_TreeView::MULTISELECT,
		'toolbar' => bab_TreeView::SHOW_TOOLBAR,
		'memorize' => bab_TreeView::MEMORIZE_OPEN_NODES
	);

	getAttributesFromRp($params, &$attributes, &$urlAttributes);

	$ignoredCategories = bab_rp('ignored_categories', '');
	$ignoredCategories = explode(',', $ignoredCategories);

	$treeView = new bab_ArticleTreeView('bab_tv_article');
	$treeView->setAttributes($attributes);
	$treeView->ignoreCategories($ignoredCategories);
	$treeView->order();
	$treeView->sort();
	
	$babBody->babPopup($treeView->printTemplate());
	die();
}


/**
 * Displays a faq selection popup.
 */
function selectFaqs()
{
	global $babBody;

	$params = array(
		'show_categories' => bab_FaqTreeView::SHOW_CATEGORIES,
		'show_sub_categories' => bab_FaqTreeView::SHOW_SUB_CATEGORIES,
		'show_questions' => bab_FaqTreeView::SHOW_QUESTIONS,
		'selectable_categories' => bab_FaqTreeView::SELECTABLE_CATEGORIES,
		'selectable_sub_categories' => bab_FaqTreeView::SELECTABLE_SUB_CATEGORIES,
		'selectable_questions' => bab_FaqTreeView::SELECTABLE_QUESTIONS,
		'multi' => bab_TreeView::MULTISELECT,
		'toolbar' => bab_TreeView::SHOW_TOOLBAR,
		'memorize' => bab_TreeView::MEMORIZE_OPEN_NODES
	);

	getAttributesFromRp($params, &$attributes, &$urlAttributes);

	$treeView = new bab_FaqTreeView('bab_tv_faq');
	$treeView->setAttributes($attributes);

	$babBody->babPopup($treeView->printTemplate());
	die();
}


/**
 * Displays a forum selection popup.
 */
function selectForums()
{
	global $babBody;

	$params = array(
		'show_forums' => bab_ForumTreeView::SHOW_FORUMS,
		'show_threads' => bab_ForumTreeView::SHOW_THREADS,
		'show_posts' => bab_ForumTreeView::SHOW_POSTS,
		'selectable_forums' => bab_ForumTreeView::SELECTABLE_FORUMS,
		'selectable_threads' => bab_ForumTreeView::SELECTABLE_THREADS,
		'selectable_posts' => bab_ForumTreeView::SELECTABLE_POSTS,
		'multi' => bab_TreeView::MULTISELECT,
		'toolbar' => bab_TreeView::SHOW_TOOLBAR,
		'memorize' => bab_TreeView::MEMORIZE_OPEN_NODES
	);

	getAttributesFromRp($params, &$attributes, &$urlAttributes);

	$treeView = new bab_ForumTreeView('bab_tv_forum');
	$treeView->setAttributes($attributes);

	$babBody->babPopup($treeView->printTemplate());
	die();
}


/**
 * Displays a file selection popup for files from ovidentia file manager.
 * 
 * This selection popup uses Ajax to dynamically load root folders subfolders.
 * 
 * If $folderId is not specified, a popup containing the root folders will be output
 * (when they are opened by the user, selectFiles will be called automatically with
 * the selected folderId as a parameter).
 * Otherwise, the function will output the sub folder tree of the folder.
 * 
 * @param int		$folderId
 * @param string	$path
 */
function selectFiles($folderId = null, $path = '')
{
	global $babBody;

	$params = array(
		'show_collective_directories' => bab_FileTreeView::SHOW_COLLECTIVE_DIRECTORIES,
		'show_personal_directories' => bab_FileTreeView::SHOW_PERSONAL_DIRECTORIES,
		'show_sub_directories' => bab_FileTreeView::SHOW_SUB_DIRECTORIES,
		'show_files' => bab_FileTreeView::SHOW_FILES,
		'selectable_collective_directories' => bab_FileTreeView::SELECTABLE_COLLECTIVE_DIRECTORIES,
		'selectable_sub_directories' => bab_FileTreeView::SELECTABLE_SUB_DIRECTORIES,
		'selectable_files' => bab_FileTreeView::SELECTABLE_FILES,
		'show_only_delegation' => bab_FileTreeView::SHOW_ONLY_ADMINISTERED_DELEGATION, // DEPRECATED
		'show_only_administered_delegation' => bab_ArticleTreeView::SHOW_ONLY_ADMINISTERED_DELEGATION,
		'multi' => bab_TreeView::MULTISELECT,
		'toolbar' => bab_TreeView::SHOW_TOOLBAR,
		'memorize' => bab_TreeView::MEMORIZE_OPEN_NODES
	);

	getAttributesFromRp($params, &$attributes, &$urlAttributes);

	$treeView = new bab_FileTreeView('bab_tv_file', $GLOBALS['babBody']->isSuperAdmin);

	$treeView->setUpdateBaseUrl('?tg=selector&idx=files' . $urlAttributes);
	
	if (!is_null($folderId)) {
		// Here we are in the case where $folderId is specified,
		// so we return only its sub folders tree. 
		$treeView->setStartPath($folderId, $path);
		$treeView->setAttributes($attributes);
		header('Content-type: text/html; charset=' . bab_charset::getIso());
		echo($treeView->printSubTree());
		die();
	}
	// Here we are in the case where $folderId is not set, so we only want to display the root folders
	// (the sub folders will be loaded when the user opens one of these)
	$attributes &= ~(bab_FileTreeView::SHOW_SUB_DIRECTORIES | bab_FileTreeView::SHOW_FILES);
	$treeView->setAttributes($attributes);
	$babBody->babPopup($treeView->printTemplate());
	die();
}


/**
 * Displays a group selection popup.
 */
function selectGroups()
{
	global $babBody;

	$params = array(
		'selectable_groups' => bab_GroupTreeView::SELECTABLE_GROUPS,
		'multi' => bab_TreeView::MULTISELECT,
		'toolbar' => bab_TreeView::SHOW_TOOLBAR,
		'memorize' => bab_TreeView::MEMORIZE_OPEN_NODES
	
	);

	getAttributesFromRp($params, &$attributes, &$urlAttributes);

	$treeView = new bab_GroupTreeView('bab_tv_groups');
	$treeView->setAttributes($attributes);
	$treeView->sort();
	$babBody->babPopup($treeView->printTemplate());
}



$idx = bab_rp('idx', '');


switch ($idx) {
	case 'articles':
		selectArticles();
		break;

	case 'faqs':
		selectFaqs();
		break;

	case 'forums':
		selectForums();
		break;

	case 'files':
		$start = bab_rp('start', null);
		if (!is_null($start)) {
			$startElements = explode(':', $start);
			$folderId = $startElements[0];
			unset($startElements[0]);
			$path = implode('/', $startElements);
			selectFiles($folderId, $path);
		} else {
			selectFiles();
		}
		break;

	case 'groups':
		selectGroups();
		break;

	default:
		break;
}
