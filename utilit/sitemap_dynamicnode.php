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


class Func_SitemapDynamicNode extends bab_functionality
{
	public function getDescription()
	{
		return bab_translate('Interface for subtree of a dynamic sitemap node');
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
	public function getSitemapItemFromRewritePath(bab_Node $node, Array $rewritePath)
	{
		
	}
}


