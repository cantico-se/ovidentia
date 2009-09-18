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


function bab_onReference(bab_eventReference $oEvent)
{
	handleFilesRequest($oEvent);
	handleArticlesRequest($oEvent);
	handleDraftArticlesRequest($oEvent);
}


function handleFilesRequest(bab_eventReference $oEvent)
{
	$sModule			= 'files.file';
	$sClassName			= 'bab_FileReferenceDescription';
	handlePublicationRequest($oEvent, $sModule, $sClassName);
}


function handleArticlesRequest($oEvent)
{
	$sModule		= 'articles.article';
	$sClassName		= 'bab_ArticleReferenceDescription';
	handlePublicationRequest($oEvent, $sModule, $sClassName);
}


function handleDraftArticlesRequest($oEvent)
{
	$sModule		= 'articles.draft';
	$sClassName		= 'bab_DraftArticleReferenceDescription';
	handlePublicationRequest($oEvent, $sModule, $sClassName);
}


function handlePublicationRequest($oEvent, $sModule, $sClassName)
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
