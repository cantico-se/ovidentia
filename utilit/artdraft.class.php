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

require_once 'base.php';
require_once dirname(__FILE__) . '/artapi.php';


class bab_ArtDraft
{
	/**
	 * @var int
	 */
	private $id_author;
	
	
	private $date_creation			= '0000-00-00 00:00:00';
	private $date_modification		= '0000-00-00 00:00:00';
	private $date_submission		= '0000-00-00 00:00:00';
	private $date_publication		= '0000-00-00 00:00:00';
	private $date_archiving			= '0000-00-00 00:00:00';
	
	/**
	 * @var string
	 */
	private $title;
	private $head;
	private $head_format = 'html';
	private $body;
	private $body_format = 'html';
	
	/**
	 * @var string
	 */
	private $lang;
	
	/**
	 * @var string Y | N
	 */
	private $trash;
	
	/**
	 * @var int
	 */
	private $id_topic;
	
	/**
	 * @var int
	 */
	private $id_article;
	
	/**
	 * 
	 * @var string
	 */
	private $restriction;
	
	/**
	 * @var string Y | N
	 */
	private $hpage_private;
	
	/**
	 * @var string Y | N
	 */
	private $hpage_public;
	
	/**
	 * @var string Y | N
	 */
	private $notify_members;
	
	/**
	 * Update de modification date of article on draft submit
	 * @var string Y | N
	 */
	private $update_datemodif;
	
	/**
	 * Approbation instance
	 * @var int
	 */
	private $idfai;
	
	/**
	 * 
	 */
	private $result;
	
	
	/**
	 * @var unknown_type
	 */
	private $id_anonymous;
	
	/**
	 * @var int		1 | 2 | 3
	 */
	private $approbation;
	
	
}