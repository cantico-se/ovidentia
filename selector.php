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
include_once "base.php";

require_once $GLOBALS['babInstallPath'] . 'utilit/uiutil.php';
require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';

function selectArticles()
{
	$attributes = 0;
	if (isset($_REQUEST['show_categories']))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SHOW_CATEGORIES;
	if (isset($_REQUEST['show_topics']))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS;
	if (isset($_REQUEST['show_articles']))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES;
	if (isset($_REQUEST['selectable_categories']))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SELECTABLE_CATEGORIES;
	if (isset($_REQUEST['selectable_topics']))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS;
	if (isset($_REQUEST['selectable_articles']))
		$attributes |= BAB_ARTICLE_TREE_VIEW_SELECTABLE_ARTICLES;

	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

	$treeView = new bab_ArticleTreeView('bab_tv_article');
	$treeView->setAttributes($attributes);

	$GLOBALS['babBodyPopup']->babecho($treeView->printTemplate());
	printBabBodyPopup();
	die();
}

function selectFaqs()
{
	$attributes = 0;
	if (isset($_REQUEST['show_categories']))
		$attributes |= BAB_FAQ_TREE_VIEW_SHOW_CATEGORIES;
	if (isset($_REQUEST['show_sub_categories']))
		$attributes |= BAB_FAQ_TREE_VIEW_SHOW_SUB_CATEGORIES;
	if (isset($_REQUEST['show_questions']))
		$attributes |= BAB_FAQ_TREE_VIEW_SHOW_QUESTIONS;

	if (isset($_REQUEST['selectable_categories']))
		$attributes |= BAB_FAQ_TREE_VIEW_SELECTABLE_CATEGORIES;
	if (isset($_REQUEST['selectable_sub_categories']))
		$attributes |= BAB_FAQ_TREE_VIEW_SELECTABLE_SUB_CATEGORIES;
	if (isset($_REQUEST['selectable_questions']))
		$attributes |= BAB_FAQ_TREE_VIEW_SELECTABLE_QUESTIONS;

	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
	
	$treeView = new bab_FaqTreeView('bab_tv_faq');
	$treeView->setAttributes($attributes);

	$GLOBALS['babBodyPopup']->babecho($treeView->printTemplate());
	printBabBodyPopup();
	die();
}

function selectForums()
{
	$attributes = 0;
	if (isset($_REQUEST['show_forums']))
		$attributes |= BAB_FORUM_TREE_VIEW_SHOW_FORUMS;
	if (isset($_REQUEST['show_threads']))
		$attributes |= BAB_FORUM_TREE_VIEW_SHOW_THREADS;
	if (isset($_REQUEST['show_posts']))
		$attributes |= BAB_FORUM_TREE_VIEW_SHOW_POSTS;

	if (isset($_REQUEST['selectable_forums']))
		$attributes |= BAB_FORUM_TREE_VIEW_SELECTABLE_FORUMS;
	if (isset($_REQUEST['selectable_threads']))
		$attributes |= BAB_FORUM_TREE_VIEW_SELECTABLE_THREADS;
	if (isset($_REQUEST['selectable_posts']))
		$attributes |= BAB_FORUM_TREE_VIEW_SELECTABLE_POSTS;
	
	
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
	
	$treeView = new bab_ForumTreeView('bab_tv_forum');
	$treeView->setAttributes($attributes);

	$GLOBALS['babBodyPopup']->babecho($treeView->printTemplate());
	printBabBodyPopup();
	die();
}


function selectFiles()
{
	$attributes = 0;
	if (isset($_REQUEST['show_collective_directories']))
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_COLLECTIVE_DIRECTORIES;
	if (isset($_REQUEST['show_personal_directories']))
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_PERSONAL_DIRECTORIES;
		if (isset($_REQUEST['show_sub_directories']))
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_SUB_DIRECTORIES;
	if (isset($_REQUEST['show_files']))
		$attributes |= BAB_FILE_TREE_VIEW_SHOW_FILES;

	if (isset($_REQUEST['selectable_collective_directories']))
		$attributes |= BAB_FILE_TREE_VIEW_SELECTABLE_COLLECTIVE_DIRECTORIES;
	if (isset($_REQUEST['selectable_sub_directories']))
		$attributes |= BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES;
	if (isset($_REQUEST['selectable_files']))
		$attributes |= BAB_FILE_TREE_VIEW_SELECTABLE_FILES;
	
	if (isset($_REQUEST['multi']))
		$attributes |= BAB_TREE_VIEW_MULTISELECT;

	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
	
	$treeView = new bab_FileTreeView('bab_tv_file', $GLOBALS['babBody']->isSuperAdmin);
	$treeView->setAttributes($attributes);

	$GLOBALS['babBodyPopup']->babecho($treeView->printTemplate());
	printBabBodyPopup();
	die();
}



function selectGroups() {
	
	$treeView = new bab_GroupTreeView('bab_tv_groups');
	$treeView->sort();
	$GLOBALS['babBody']->babpopup($treeView->printTemplate());
}



$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : '';


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
		selectFiles();
		break;

	case 'groups':
		selectGroups();
		break;
		
	default:
		break;
}


?>