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
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'admin/register.php';

function bab_menuDisplay()
{
    /* @var Func_Icons $I */
    $I = bab_functionality::get('Icons');
    $I->includeCss();

    $W = bab_Widgets();
    /* @var $W Func_Widgets */

    $sitemap = bab_siteMap::getByUid('core');

    $page = $W->babPage('bab-menu');

    $completeLayout = $W->FLowLayout()->setVerticalAlign('top');
    $completeLayout->setHorizontalSpacing(2.5,'em');
    $completeLayout->addClass('BabLoginMenuBackground');
    $completeLayout->addClass('widget-bordered');
    $completeLayout->addClass('icon-16x16 icon-left-16');

    $adminLayout = $W->VBoxLayout();
    $adminLink = false;
    $adminLayout->addItem($W->Title(bab_translate('Administration')));

    /*ADMIN OVI*/
    $layout = $W->ListLayout();
    $adminLayout->addItem($layout);
    $node = $sitemap->getNodeById('babAdminSection');
    if($node){
        $node = $node->firstChild();
        $tempLink = array();
        while($node){
            $adminLink = true;
            $item = $node->getData();
            $tempLink[] = array('name' => $item->name, 'url' => $item->url, 'class' => $item->iconClassnames);
            $node = $node->nextSibling();
        }

        bab_Sort::asort($tempLink, 'name');

        foreach($tempLink as $link){
            $layout->addItem($W->Link($link['name'], $link['url'])->addClass('icon '.$link['class']));
        }

        $adminLayout->addItem($W->Html('<hr>'));
    }

    /*ADMIN ADDON*/
    $layout = $W->ListLayout();
    $adminLayout->addItem($layout);
    $node = $sitemap->getNodeById('babAdminSectionAddons');
    if($node){
        $node = $node->firstChild();
        $tempLink = array();
        while($node){
            $adminLink = true;
            $item = $node->getData();
            $tempLink[] = array('name' => $item->name, 'url' => $item->url, 'class' => $item->iconClassnames);
            $node = $node->nextSibling();
        }

        bab_Sort::asort($tempLink, 'name');

        foreach($tempLink as $link){
            $layout->addItem($W->Link($link['name'], $link['url'])->addClass('icon '.$link['class']));
        }
    }

    if($adminLink){
        $completeLayout->addItem($adminLayout);
    }

    ///////////////////////////////////

    $userLayout = $W->VBoxLayout();
    $completeLayout->addItem($userLayout);

    $userLayout->addItem($W->Title(bab_translate('User')));

    /*USER OVI*/
    $layout = $W->ListLayout();
    $userLayout->addItem($layout);
    $node = $sitemap->getNodeById('babUserSection');
    if($node){
        $node = $node->firstChild();
        $tempLink = array();
        while($node){
            $item = $node->getData();
            $tempLink[] = array('name' => $item->name, 'url' => $item->url, 'class' => $item->iconClassnames);
            $node = $node->nextSibling();
        }

        bab_Sort::asort($tempLink, 'name');

        foreach($tempLink as $link){
            $layout->addItem($W->Link($link['name'], $link['url'])->addClass('icon '.$link['class']));
        }

        $userLayout->addItem($W->Html('<hr>'));
    }

    /*USER ADDON*/
    $layout = $W->ListLayout();
    $userLayout->addItem($layout);
    $node = $sitemap->getNodeById('babUserSectionAddons');
    if($node){
        $node = $node->firstChild();
        $tempLink = array();
        while($node){
            $item = $node->getData();
            $tempLink[] = array('name' => $item->name, 'url' => $item->url, 'class' => $item->iconClassnames);
            $node = $node->nextSibling();
        }

        bab_Sort::asort($tempLink, 'name');

        foreach($tempLink as $link){
            $layout->addItem($W->Link($link['name'], $link['url'])->addClass('icon '.$link['class']));
        }
    }

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
?>
