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
	 * @return string
	 */
	private function getArticleUrl($id_topic, $id_article)
	{
		require_once dirname(__FILE__).'/urlincl.php';
		
		$url = new bab_url();
		$url->tg = 'articles';
		$url->idx = 'More';
		$url->topics = $id_topic;
		$url->article = $id_article;
		
		
		return $url->toString();
	}
	
	
	
	/**
	 * 
	 * @param int $id_topic
	 * @param int $id_article
	 * @param string $articleTitle
	 * @param string $rewriteName
	 * @return bab_siteMapItem
	 */
	private function getArticleSitemapItem($id_topic, $id_article, $articleTitle, $rewriteName)
	{
		$item = new bab_siteMapItem();
		$item->id_function 		= 'babArticle_'.$id_article;
		$item->name 			= $articleTitle;
		$item->url 				= $this->getArticleUrl($id_topic, $id_article);
		$item->folder 			= false;
		$item->iconClassnames	= Func_Icons::OBJECTS_PUBLICATION_ARTICLE;
		$item->rewriteName		= $rewriteName;
		
		return $item;
	}
	

	
	/**
	 * Get a list of sitemap items from rewrite path
	 * rewrite path is relative to the dynamic node, this method return one sitemap item forea each rewrite name in rewrite path, in the same order
	 * 
	 * @param	bab_Node	$node				The dynamic sitemap node
	 * @param 	Array		$rewritePath		Relative rewrite path from node to required sitemap item
	 * 
	 * @return array
	 */
	public function getSitemapItemsFromRewritePath(bab_Node $node, Array $rewritePath)
	{
		
		bab_functionality::includeOriginal('Icons');
		
		$id_function = $node->getId();
		$id_topic = (int) mb_substr($id_function, 16);
		$rewritename = reset($rewritePath);
		
		global $babDB;
		
		
		
		$res = $babDB->db_query('SELECT id, title FROM bab_articles WHERE id_topic='.$babDB->quote($id_topic).' AND rewritename='.$babDB->quote($rewritename));
		
		if (1 !== $babDB->db_num_rows($res))
		{
			return null;
		}
		
		$article = $babDB->db_fetch_assoc($res);
		return array($this->getArticleSitemapItem($id_topic, $article['id'], $article['title'], $rewritename));
	}
	
	
	
	/**
	 * Get a list of sitemap items from node ID
	 * this method return one sitemap item for each node beetween the dynamic node and the nodeId, sitemap item for nodeId included
	 *
	 * @param bab_Node $node
	 * @param string $nodeId
	 * @throws Exception
	 *
	 * @return array
	 */
	public function getSitemapItemsFromNodeId(bab_Node $node, $nodeId)
	{
		bab_functionality::includeOriginal('Icons');
		
		$id_article = (int) mb_substr($nodeId, 11); // babArticle_
		
		global $babDB;
		
		
		
		$res = $babDB->db_query('SELECT id_topic, title, rewritename FROM bab_articles WHERE id='.$babDB->quote($id_article).'');
		
		if (1 !== $babDB->db_num_rows($res))
		{
			return null;
		}
		
		$article = $babDB->db_fetch_assoc($res);
		return array($this->getArticleSitemapItem($article['id_topic'], $id_article, $article['title'], $article['rewritename']));
	}
}
