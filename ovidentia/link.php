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
require_once dirname(__FILE__).'/utilit/urlincl.php';

/**
 * Test if a direct redirect is allowed
 * @param string $url
 * @return bool
 */
function bab_isRedirectValid($url)
{
    $rootNode = bab_siteMap::getFromSite();
    if (!isset($rootNode)) {
        return false;
    }
    
    $redirectedUrl = $GLOBALS['babUrl'];
    $redirectedUrl = bab_url::mod($redirectedUrl, 'tg', 'link');
    $redirectedUrl = bab_url::mod($redirectedUrl, 'url', $url);
    
    $nodes = $rootNode->getNodesByIndex('url', $redirectedUrl);
    return (count($nodes) > 0);
}

/**
 * Display a page to confirm redirect
 * @param string $url
 */
function bab_confirmRedirect($url)
{
    $W = bab_Widgets();
    $page = $W->BabPage();
    
    $form = $W->Form(null, $W->VBoxLayout()->setVerticalSpacing(1, 'em'));
    $page->addItem($form);
    
    $form->addClass('widget-centered');
    $form->addClass('widget-bordered');
    $form->addClass('babLoginMenuBackground');
    
    $form->setHiddenValue('tg', bab_rp('tg'));
    $form->setHiddenValue('idx', 'confirmed');
    $form->setHiddenValue('url', $url);
    
    $form->addItem($W->Label(
        bab_translate('You are going to be redirected to an external site, please confirm')
    ));
    $form->addItem($W->Label($url));
    
    $form->addItem(
        $W->SubmitButton()
        ->setLabel(bab_translate('Confirm'))
    );
    
    $page->displayHtml();
}


/**
 * @param string $url
 */
function bab_statRedirect($url)
{
    if (isset($GLOBALS['babWebStat'])) {
        $GLOBALS['babWebStat']->addExternalLink($url);
    }
    header("Location: ". $url);
    exit;
}


switch(bab_rp('idx'))
{
    case 'confirmed':
        bab_requireSaveMethod();
        bab_statRedirect(bab_rp('url'));
        break;
    
    
	default:
		$url = bab_rp('url');
		if (bab_isRedirectValid($url)) {
		    return bab_statRedirect($url);
		}
		bab_confirmRedirect($url);
		
}