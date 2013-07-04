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


bab_functionality::includefile('SitemapDynamicNode');

class Func_SitemapDynamicNode_Topic extends Func_SitemapDynamicNode
{
	public function getDescription()
	{
		return bab_translate('Load articles as topic subnodes');
	}
	

	
	/**
	 * Get a sitemap item from rewrite path
	 * 
	 * @param	bab_Node	$node				The dynamic sitemap node
	 * @param 	Array		$rewritePath		Relative rewrite path from node to required sitemap item
	 * 
	 * @return bab_siteMapItem
	 */
	public function getSitemapItemFromRewritePath(bab_Node $node, Array $rewritePath)
	{
		require_once dirname(__FILE__).'/urlincl.php';
		bab_functionality::includeOriginal('Icons');
		
		$id_function = $node->getId();
		$id_topic = (int) mb_substr($id_function, 16);
		$rewritename = reset($rewritePath);
		
		global $babDB;
		
		
		
		$res = $babDB->db_query('SELECT id, title FROM bab_articles WHERE id_topic='.$babDB->quote($id_topic).' AND rewritename='.$babDB->quote($rewritename));
		
		if (1 !== $babDB->db_num_rows($res))
		{
			bab_debug($rewritename);
			return null;
		}
		
		$article = $babDB->db_fetch_assoc($res);
		
		$url = new bab_url();
		$url->tg = 'articles';
		$url->idx = 'More';
		$url->topics = $id_topic;
		$url->article = $article['id'];
		
		$item = new bab_siteMapItem();
		$item->id_function 		= 'babArticle'.$article['id'];
		$item->name 			= $article['title'];
		$item->url 				= $url->toString();
		$item->folder 			= false;
		$item->iconClassnames	= Func_Icons::OBJECTS_PUBLICATION_ARTICLE;
		$item->rewriteName		= $rewritename;
		
		
		return $item;
	}
}
