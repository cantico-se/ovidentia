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
require_once dirname(__FILE__).'/utilit/registerglobals.php';

$babBody = bab_getBody();
$args = array_merge($_GET, $_POST);

$file = bab_rp('file');

if(isset($args['echo']))
	{
		switch($args['echo'])
		{
			case 'raw':
			case 1:
				if(isset($args['ovmlcache']) && $args['ovmlcache'] == 1){
					echo bab_printCachedOvmlTemplate($file, $args);
				}else{
					echo bab_printOvmlTemplate($file, $args);
				}
				exit;
				
			case 'popup':
				$babBody->babPopup(bab_printOvmlTemplate($file, $args));
				break;
		}
	}
else
	{
	$babBody->babecho( bab_printOvmlTemplate($file, $args));
	}
	
	


// try to set position in sitemap if not allready done by rewriting

if (null === bab_siteMap::getPosition() && isset($_SERVER['QUERY_STRING']))
{
	
	$rootNode = bab_siteMap::getByUid($babBody->babsite['sitemap']);
	if (isset($rootNode))
	{
		if (($nodes = $rootNode->getNodesByIndex('url', '?'.$_SERVER['QUERY_STRING'])) || ($nodes = $rootNode->getNodesByIndex('url', bab_getSelf().'?'.$_SERVER['QUERY_STRING'])))
		{
			$node = reset($nodes);
			$sitemapItem = $node->getData();
			bab_siteMap::setPosition($sitemapItem->id_function);
		}
	}
}