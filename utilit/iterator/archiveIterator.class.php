<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
// 
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */
require_once 'base.php';


abstract class bab_ArchiveEntry
{
	protected $sName				= null;
	protected $sBaseName			= null;
	protected $iSize				= null;
	protected $iCompressedSize		= null;
	protected $sCompressionMethod	= null;
	
	public function __construct()
	{
	}
	
	public function __destruct()
	{
		
	}
	
	public function getBaseName()
	{
		return $this->sBaseName;
	}
	
	public function getName()
	{
		return $this->sName;
	}
	
	public function getSize()
	{
		return $this->iSize;
	}
	
	public function getCompressedSize()
	{
		return $this->iCompressedSize;
	}
	
	public function getCompressionMethod()
	{
		return $this->sCompressionMethod;
	}
}

class bab_ZipEntry extends bab_ArchiveEntry
{
	private $oZipArchive = null;
	private $iIndex = null;
	
	public function __construct(/* $oZipArchive, $aInfo */)
	{
		parent::__construct();
		
		$iNumArgs = func_num_args();
		if(2 != $iNumArgs)
		{
			return;
		}
		
		$this->oZipArchive = func_get_arg(0);
		$aInfo = func_get_arg(1);
		
		/*
		Array
		(
		    [name] => foobar/baz
		    [index] => 3
		    [crc] => 499465816
		    [size] => 27
		    [mtime] => 1123164748
		    [comp_size] => 24
		    [comp_method] => 8
		)
		//*/
		
		$this->sName				= $aInfo['name'];
		$this->sBaseName			= basename($aInfo['name']);
		$this->iSize				= $aInfo['size'];
		$this->iCompressedSize		= $aInfo['comp_size']; 
		$this->sCompressionMethod	= $aInfo['comp_method'];
		$this->iIndex				= $aInfo['index'];
	}
}






abstract class bab_ArchiveIterator implements Iterator
{
	protected $sFullPathName	= null;
	protected $iKey				= bab_ArchiveIterator::EOF;
	protected $oResource		= null;
	protected $oObject			= null;
	
	const EOF = -1;
	
	public function __contruct()
	{
	}

	public function __destruct()
	{
		$this->closeArchive();
	}

	//Iterator interface function implementation

	public function rewind()
	{
		$this->closeArchive();
		$this->openArchive();
		
		$this->oObject	= null;
		$this->iKey		= 0;
	}
	
	public function key()   
	{
		return $this->iKey;
	}
	
	public function current()
	{
		if(is_null($this->oObject))
		{
			$this->next();
		}
		return $this->oObject;
	}

	public function next()
	{
		if(is_object($this->oResource))
		{
			$aInfo = $this->nextEntry();
			if(isset($aInfo))
			{
				$this->iKey++;
				$this->oObject = $this->getObject($aInfo);
				return;
			}
		}
		
		$this->oObject = null;
		$this->iKey = bab_ArchiveIterator::EOF;
	}

	public function valid()
	{
		if(0 != $this->iKey && bab_ArchiveIterator::EOF != $this->iKey)
		{
			return true;
		}
		else if(0 == $this->iKey)
		{
			$this->next();
			return $this->valid();
		}
		else if(bab_ArchiveIterator::EOF == $this->iKey)
		{
			return false;
		}
		return false;
	}


	//Helper function
	
	public function setFullPathName($sFullPathName)
	{
		$this->sFullPathName = $sFullPathName;
	}

	abstract protected function openArchive();

	abstract protected function closeArchive();
	
	abstract protected function nextEntry();
	
	abstract protected function getObject($oEntry);
}


class bab_ZipIterator extends bab_ArchiveIterator
{
	private $iCount = 0;
	
	public function __contruct()
	{
		parent::__contruct();
	}

	public function __destruct()
	{
		parent::__destruct();
	}


	protected function openArchive()
	{
		if(class_exists('ZipArchive'))
		{
			$this->oResource = new ZipArchive();
			if(!$this->oResource->open($this->sFullPathName))
			{
				unset($this->oResource);
				$this->oResource = null;
				$this->iCount = 0;
				return;	
			}
			
			$this->iCount = $this->oResource->numFiles;
		}
	}

	protected function closeArchive()
	{
		if(is_object($this->oResource))
		{
			if($this->oResource->close())
			{
				unset($this->oResource);
				$this->oResource = null;
			}
		}
	}
	
	protected function nextEntry()
	{
		$aInfo = $this->oResource->statIndex($this->iKey);
		if(false !== $aInfo)
		{	
			return $aInfo;
		}
		return null;
	}
	
	protected function getObject($aInfo)
	{
		return new bab_ZipEntry($this->oResource, $aInfo);
	}
}
