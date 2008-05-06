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


/**
 * Generate pagnination array
 *
 * @param	int		$num_items		Total number of items
 * @param	int		$per_page		Number of elements per pages
 * @param	int		$start_item		Current position
 *
 * @return 	array
 */
function bab_generatePagination( $num_items, $per_page, $start_item, $add_prevnext_text = TRUE)
{
	global $lang;

	$total_pages = ceil($num_items/$per_page);

	if ( $total_pages == 1 )
	{
		return array();
	}

	$on_page = floor($start_item / $per_page) + 1;

	$page_array = array();

	if ( $total_pages > 10 )
	{
		$init_page_max = ( $total_pages > 3 ) ? 3 : $total_pages;

		for($i = 1; $i < $init_page_max + 1; $i++)
		{
			$page_array[] = ( $i == $on_page ) ? array('page'=>$i, 'pagepos'=>( ( $i - 1 ) * $per_page ), 'current'=> true, 'url'=>false): array('page'=>$i, 'pagepos'=>( ( $i - 1 ) * $per_page ), 'current'=> false, 'url'=>true);
			if ( $i <  $init_page_max )
			{
				$page_array[] =  array('page'=>', ', 'pagepos'=>0, 'current'=> false, 'url'=>false);
			}
		}

		if ( $total_pages > 3 )
		{
			if ( $on_page > 1  && $on_page < $total_pages )
			{
				if( $on_page > 5 )
					$page_array[] =  array('page'=>'...', 'pagepos'=>0, 'current'=> false, 'url'=>false);
				else
					$page_array[] =  array('page'=>', ', 'pagepos'=>0, 'current'=> false, 'url'=>false);

				$init_page_min = ( $on_page > 4 ) ? $on_page : 5;
				$init_page_max = ( $on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;

				for($i = $init_page_min - 1; $i < $init_page_max + 2; $i++)
				{
					if($i == $on_page)
						$page_array[] =  array('page'=>$i, 'pagepos'=>0, 'current'=> true, 'url'=>false);
					else
						$page_array[] =  array('page'=>$i, 'pagepos'=>( ( $i - 1 ) * $per_page ), 'current'=> false, 'url'=>true);

					if ( $i <  $init_page_max + 1 )
					{
						$page_array[] =  array('page'=>', ', 'pagepos'=>0, 'current'=> false, 'url'=>false);
					}
				}

				if( $on_page < $total_pages - 4 )
					$page_array[] =  array('page'=>'...', 'pagepos'=>0, 'current'=> false, 'url'=>false);
				else
					$page_array[] =  array('page'=>', ', 'pagepos'=>0, 'current'=> false, 'url'=>false);
			}
			else
			{
				$page_array[] =  array('page'=>'...', 'pagepos'=>0, 'current'=> false, 'url'=>false);
			}

			for($i = $total_pages - 2; $i < $total_pages + 1; $i++)
			{
				if( $i == $on_page )
					$page_array[] =  array('page'=>$i, 'pagepos'=>0, 'current'=> true, 'url'=>false);
				else
					$page_array[] =  array('page'=>$i, 'pagepos'=>( ( $i - 1 ) * $per_page ), 'current'=> false, 'url'=>true);

				if( $i <  $total_pages )
				{
					$page_array[] =  array('page'=>', ', 'pagepos'=>0, 'current'=> false, 'url'=>false);
				}
			}
		}
	}
	else
	{
		for($i = 1; $i < $total_pages + 1; $i++)
		{
			if( $i == $on_page )
					$page_array[] =  array('page'=>$i, 'pagepos'=>0, 'current'=> true, 'url'=>false);
				else
					$page_array[] =  array('page'=>$i, 'pagepos'=>( ( $i - 1 ) * $per_page ), 'current'=> false, 'url'=>true);

			if ( $i <  $total_pages )
			{
				$page_array[] =  array('page'=>', ', 'pagepos'=>0, 'current'=> false, 'url'=>false);
			}
		}
	}

	if ( $add_prevnext_text )
	{
		if ( $on_page > 1 )
		{
			$page_array =  array_pad($page_array, -(count($page_array)+1), array('page'=>bab_translate("Previous"), 'pagepos'=>( ( $on_page - 2 ) * $per_page ), 'current'=> false, 'url'=>true));
		}

		if ( $on_page < $total_pages )
		{
			$page_array[] =  array('page'=>bab_translate("Next"), 'pagepos'=>( $on_page * $per_page ), 'current'=> false, 'url'=>true);
		}

	}

	return $page_array;
}







/**
 * Generate a pagination string based on current URL
 *
 * @param	int		$num_items		Total number of items
 * @param	int		$per_page		Number of elements per pages
 * @param	int		$start_item		Current position
 * @param	string	$pos_param		name of the position parameter as specified in url
 * 
 * @return 	string					HTML string
 */
function bab_generatePaginationString($num_items, $per_page, $start_item, $pos_param) {
	
	include_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';
	$links = bab_generatePagination($num_items, $per_page, $start_item);
	$currenturl = bab_url::request_gp();
	
	$html = '';
	
	foreach($links as $key => $page) {

		if ($page['url']) {
			$url = bab_url::mod($currenturl, $pos_param, $page['pagepos']);
			$html .= sprintf(' <a href="%s">%s</a>', bab_toHtml($url), bab_toHtml($page['page']));
		} else {
			$html .= bab_toHtml($page['page']);
		}
	}
	
	return $html;
}

