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
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */
include_once "base.php";

require_once $GLOBALS['babInstallPath'] . 'utilit/uiutil.php';
require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';



/**
 * Displays an article selection popup.
 */
function selectArticles()
{
	$attributes = 0;
	if (bab_rp('show_categories', false))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SHOW_CATEGORIES;
	if (bab_rp('show_topics', false))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS;
	if (bab_rp('show_articles', false))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES;
	if (bab_rp('selectable_categories', false))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SELECTABLE_CATEGORIES;
	if (bab_rp('selectable_topics', false))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS;
	if (bab_rp('selectable_articles', false))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SELECTABLE_ARTICLES;

//	$GLOBALS['babBodyPopup'] = new babBodyPopup();
//	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
//	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

	$treeView = new bab_ArticleTreeView('bab_tv_article');
	$treeView->setAttributes($attributes);
	$treeView->order();
	$treeView->sort();
	
	$GLOBALS['babBody']->babPopup($treeView->printTemplate());
	die();
}


/**
 * Displays a faq selection popup.
 */
function selectFaqs()
{
	$attributes = 0;
	if (bab_rp('show_categories', false))
		$attributes |= BAB_FAQ_TREE_VIEW_SHOW_CATEGORIES;
	if (bab_rp('show_sub_categories', false))
		$attributes |= BAB_FAQ_TREE_VIEW_SHOW_SUB_CATEGORIES;
	if (bab_rp('show_questions', false))
		$attributes |= BAB_FAQ_TREE_VIEW_SHOW_QUESTIONS;

	if (bab_rp('selectable_categories', false))
		$attributes |= BAB_FAQ_TREE_VIEW_SELECTABLE_CATEGORIES;
	if (bab_rp('selectable_sub_categories', false))
		$attributes |= BAB_FAQ_TREE_VIEW_SELECTABLE_SUB_CATEGORIES;
	if (bab_rp('selectable_questions', false))
		$attributes |= BAB_FAQ_TREE_VIEW_SELECTABLE_QUESTIONS;

//	$GLOBALS['babBodyPopup'] = new babBodyPopup();
//	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
//	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
	
	$treeView = new bab_FaqTreeView('bab_tv_faq');
	$treeView->setAttributes($attributes);

	$GLOBALS['babBody']->babPopup($treeView->printTemplate());
	die();
}


/**
 * Displays a forum selection popup.
 */
function selectForums()
{
	$attributes = 0;
	if (bab_rp('show_forums', false))
		$attributes |= BAB_FORUM_TREE_VIEW_SHOW_FORUMS;
	if (bab_rp('show_threads', false))
		$attributes |= BAB_FORUM_TREE_VIEW_SHOW_THREADS;
	if (bab_rp('show_posts', false))
		$attributes |= BAB_FORUM_TREE_VIEW_SHOW_POSTS;

	if (bab_rp('selectable_forums', false))
		$attributes |= BAB_FORUM_TREE_VIEW_SELECTABLE_FORUMS;
	if (bab_rp('selectable_threads', false))
		$attributes |= BAB_FORUM_TREE_VIEW_SELECTABLE_THREADS;
	if (bab_rp('selectable_posts', false))
		$attributes |= BAB_FORUM_TREE_VIEW_SELECTABLE_POSTS;
	
//	$GLOBALS['babBodyPopup'] = new babBodyPopup();
//	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
//	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
	
	$treeView = new bab_ForumTreeView('bab_tv_forum');
	$treeView->setAttributes($attributes);

	$GLOBALS['babBody']->babPopup($treeView->printTemplate());
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
	$attributes = 0;
	$urlAttributes = '';
	if (bab_rp('show_collective_directories', false)) {
		$urlAttributes .= '&show_collective_directories=1';
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_COLLECTIVE_DIRECTORIES;
	}
	if (bab_rp('show_personal_directories', false)) {
		$urlAttributes .= '&show_personal_directories=1';
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_PERSONAL_DIRECTORIES;
	}
	if (bab_rp('show_sub_directories', false)) {
		$urlAttributes .= '&show_sub_directories=1';
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_SUB_DIRECTORIES;
	}
	if (bab_rp('show_files', false)) {
		$urlAttributes .= '&show_files=1';
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_FILES;
	}
	if (bab_rp('show_only_delegation', false)) {
		$urlAttributes .= '&show_only_delegation=1';
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_ONLY_DELEGATION;
	}
	if (bab_rp('selectable_collective_directories', false)) {
		$urlAttributes .= '&selectable_collective_directories=1';
		$attributes |= BAB_FILE_TREE_VIEW_SELECTABLE_COLLECTIVE_DIRECTORIES;
	}
	if (bab_rp('selectable_sub_directories', false)) {
		$urlAttributes .= '&selectable_sub_directories=1';
		$attributes |= BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES;
	}
	if (bab_rp('selectable_files', false)) {
		$urlAttributes .= '&selectable_files=1';
		$attributes |= BAB_FILE_TREE_VIEW_SELECTABLE_FILES;
	}
	
	if (bab_rp('multi', false)) {
		$urlAttributes .= '&multi=1';
		$attributes |= BAB_TREE_VIEW_MULTISELECT;
	}

//	$GLOBALS['babBodyPopup'] = new babBodyPopup();
//	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
//	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
	
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
	$attributes &= ~(BAB_FILE_TREE_VIEW_SHOW_SUB_DIRECTORIES | BAB_FILE_TREE_VIEW_SHOW_FILES);
	$treeView->setAttributes($attributes);
	$GLOBALS['babBody']->babPopup($treeView->printTemplate());
	die();
}


/**
 * Displays a group selection popup.
 */
function selectGroups()
{
	$attributes = 0;
	if (bab_rp('selectable_groups', false) !== false) {
		$attributes |= BAB_GROUP_TREE_VIEW_SELECTABLE_GROUPS;
	}
	if (bab_rp('multi', false) !== false) {
		$attributes |= BAB_TREE_VIEW_MULTISELECT;
	}
	
	$treeView = new bab_GroupTreeView('bab_tv_groups');
	$treeView->setAttributes($attributes);
	$treeView->sort();
	$GLOBALS['babBody']->babPopup($treeView->printTemplate());
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
