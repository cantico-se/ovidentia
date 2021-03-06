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
* @internal SEC1 NA 18/12/2006 FULL
*/
include_once 'base.php';

include_once $GLOBALS['babInstallPath'].'admin/register.php';

function bab_menuSubNode($node)
{
    $W = bab_Widgets();
	$canvas = null;
	if($canvas === null) {
		$canvas = $W->HtmlCanvas();
	}

    $layout = $W->ListLayout();
    $node = $node->firstChild();
    $tempLink = array();
    $link = false;
    while($node){
        $link = true;
        $item = $node->getData();
        $tempLink[] = array('name' => $item->name, 'url' => $item->url, 'class' => $item->iconClassnames, 'node' => $node);
        $node = $node->nextSibling();
    }

    if (!$link) {
        return null;
    }

    bab_Sort::asort($tempLink, 'name');


    foreach($tempLink as $link){
    	$content = '';
        if ($link['url']) {
            $content.= $W->Link($link['name'], $link['url'])->addClass('bab-menu-item icon '.$link['class'])->display($canvas);
        } else {
            $content.= $W->Label($link['name'])->addClass('bab-menu-item widget-strong icon '.$link['class'])->display($canvas);
        }
		$subNode = bab_menuSubNode($link['node']);
		if ($subNode) {
        	$content.= bab_menuSubNode($link['node'])->display($canvas);
		}
		$layout->addItem($W->Html($content));
    }

    //$layout->addItem($W->Html('<hr>'));
    return $layout;
}

function bab_menuDisplay()
{
    $sitemap = bab_siteMap::getByUid('core');

    $nodes = bab_gp('nodes', false);
    if (!$nodes) {
        $nodes = array('babAdmin', 'babUser');
    } elseif($nodes == '*') {
        $rootnode = $sitemap->getNodeById('DGAll');
        $subnode = $rootnode->firstChild();
        $nodes = array();
        while($subnode){
            $item = $subnode->getData();
            $nodes[] = $item->id_function;
            $subnode = $subnode->nextSibling();
        }
    } else {
        $nodes = explode(',', $nodes);
    }

    /* @var Func_Icons $I */
    $I = bab_functionality::get('Icons');
    $I->includeCss();

    $W = bab_Widgets();
    /* @var $W Func_Widgets */

    $page = $W->babPage('bab-menu');

	$page->addJavascriptFile($GLOBALS['babScriptPath'].'menu.js');

	$completeLayout = $W->VboxLayout('bab-menu-page');
    $completeLayout->setVerticalSpacing(1,'em');
    $completeLayout->addClass('BabLoginMenuBackground');
    $completeLayout->addClass('widget-bordered');
    $completeLayout->addClass(Func_Icons::ICON_LEFT_16);

    $container = $W->FLowLayout()->setVerticalAlign('top');
	$container->setHorizontalSpacing(2.5,'em');

	/*$completeLayout->addItem(
		$W->Form()->addItem($W->LineEdit('bab-menu-search')->setPlaceHolder(bab_translate('Search'))->setName('search'))
	);*/

    foreach($nodes as $nodeName) {
        $node = $sitemap->getNodeById($nodeName);
        if($node) {
            $item = $node->getData();
            $layout = $W->VBoxLayout();
            $Link = false;
            $layout->addItem($W->Title($item->name));

            $layout->addItem(bab_menuSubNode($node));

            $container->addItem($layout);
        }
    }

    $completeLayout->addItem($container);
	$page->addItem($completeLayout);
    $page->displayHtml();
}

$idx = bab_rp('idx', '');

switch($idx)
{
    default:
        global $babBody;
        $babBody->title = bab_translate("Menu");
        bab_menuDisplay();
        break;
}

bab_siteMap::setPosition('bab', 'Menu');

