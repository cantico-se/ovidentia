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
require_once dirname(__FILE__) . '/eventincl.php';
require_once dirname(__FILE__) . '/reference.class.php';


/**
 * Event fired when reference descriptions are needed for a list of references
 * @see bab_Reference::getReferenceDescription()
 * @see bab_fireEvent
 */
class bab_eventReference extends bab_event 
{
	private $oRefMapStorage		= null;
	private $oRefDescMapStorage	= null;
	
	public function __construct(bab_StorageMap $oRefMapStorage, bab_StorageMap $oRefDescMapStorage)
	{
		$this->oRefMapStorage		= $oRefMapStorage;
		$this->oRefDescMapStorage	= $oRefDescMapStorage;
	}
	
	public function getReferences($sModule)
	{
		return $this->oRefMapStorage->get($sModule);
	}
	
	public function getReferencesDescriptions($sModule)
	{
		return $this->oRefDescMapStorage->get($sModule);
	}
	
	public function getReferenceStorage()
	{
		return $this->oRefMapStorage;
	}
	
	public function getReferenceDescriptionStorage()
	{
		return $this->oRefDescMapStorage;
	}
}

/**
 * 
 * @param bab_eventReference $oEvent
 * @return unknown_type
 */
function bab_onReference(bab_eventReference $oEvent)
{
	
	handlePublicationRequest($oEvent, 'articles.article'		, 'bab_ArticleReferenceDescription');
	handlePublicationRequest($oEvent, 'articles.draft'			, 'bab_DraftArticleReferenceDescription');
	handlePublicationRequest($oEvent, 'files.file'				, 'bab_FileReferenceDescription');
	handlePublicationRequest($oEvent, 'files.folder'			, 'bab_FolderReferenceDescription');
	handlePublicationRequest($oEvent, 'files.personnalfolder'	, 'bab_PersonnalFolderReferenceDescription');
	handlePublicationRequest($oEvent, 'ovml.file'				, 'bab_OvmlFileReferenceDescription');
	handlePublicationRequest($oEvent, 'sitemap.node'			, 'bab_SitemapNodeReferenceDescription');
	handlePublicationRequest($oEvent, 'sitemap.url'				, 'bab_SitemapUrlReferenceDescription');
}



/**
 * Create the reference description objects needed for each reference
 * @param bab_eventReference 	$oEvent
 * @param string 				$sModule			module filter
 * @param string 				$sClassName			reference description classname to use for the coresponding module filter
 * @return unknown_type
 */
function handlePublicationRequest(bab_eventReference $oEvent, $sModule, $sClassName)
{
	$oRefStorage		= $oEvent->getReferences($sModule);
	$oRefDescStorage	= $oEvent->getReferencesDescriptions($sModule);
	
	if($oRefStorage instanceof bab_ObjectStorage && $oRefDescStorage instanceof bab_ObjectStorage)
	{
		foreach($oRefStorage as $oReference)
		{
			$oRefDescStorage->attach(new $sClassName($oReference));
		}
	}
}
