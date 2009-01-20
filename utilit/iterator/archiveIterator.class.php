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
	public function __construct(/* $oZip, $oZipEntry */)
	{
		parent::__construct();
		
		$iNumArgs = func_num_args();
		if(2 != $iNumArgs)
		{
			return;
		}
		
		$oZip = func_get_arg(0);
		$oZipEntry = func_get_arg(1);
		
		if(zip_entry_open($oZip, $oZipEntry))
		{
			$this->sName				= zip_entry_name($oZipEntry);
			$this->sBaseName			= basename($this->sName);
			$this->iSize				= zip_entry_filesize($oZipEntry);
			$this->iCompressedSize		= zip_entry_compressedsize($oZipEntry); 
			$this->sCompressionMethod	= zip_entry_compressionmethod($oZipEntry);
			zip_entry_close($oZipEntry);
		}
	}
}






abstract class bab_ArchiveIterator implements Iterator
{
	protected $sFullPathName	= null;
	protected $iKey				= bab_ArchiveIterator::EOF;
	protected $oRessource		= null;
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
		if(is_resource($this->oRessource))
		{
			$oEntry = $this->nextEntry();
			if(is_resource($oEntry))
			{
				$this->iKey++;
				$this->oObject = $this->getObject($oEntry);
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
		if(function_exists('zip_open'))
		{
			$this->oRessource = zip_open($this->sFullPathName);
		}
	}

	protected function closeArchive()
	{
		if(is_resource($this->oRessource))
		{
			zip_close($this->oRessource);
			$this->oRessource = null;
		}
	}
	
	protected function nextEntry()
	{
		return zip_read($this->oRessource);
	}
	
	protected function getObject($oEntry)
	{
		return new bab_ZipEntry($this->oRessource, $oEntry);
	}
}
?>