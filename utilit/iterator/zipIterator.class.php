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


class bab_ZipEntry
{
	private $sName				= null;
	private $sBaseName			= null;
	private $iSize				= null;
	private $iCompressedSize	= null;
	private $sCompressionMethod	= null;
	
	public function __construct($oZip, $oZipEntry)
	{
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


class bab_ZipIterator implements Iterator
{
	private $_sFullPathName	= null;
	private $_iKey			= bab_ZipIterator::EOF;
	private $_oZip			= null;
	private $_oObject		= null;
	const	EOF				= -1;
	
	public function __contruct()
	{
	}

	public function __destruct()
	{
		$this->closeZip();
	}

	//Iterator interface function implementation

	public function rewind()
	{
		$this->closeZip();
		$this->openZip();
		
		$this->_oObject	= null;
		$this->_iKey	= 0;
	}
	
	public function key()   
	{
		return $this->_iKey;
	}
	
	public function current()
	{
		if(is_null($this->_oObject))
		{
			$this->next();
		}
		return $this->_oObject;
	}

	public function next()
	{
		if(is_resource($this->_oZip))
		{
			$oZipEntry = zip_read($this->_oZip);
			if(is_resource($oZipEntry))
			{
				$this->_iKey++;
				$this->_oObject = $this->getObject($oZipEntry);
				return;
			}
		}
		
		$this->_oObject = null;
		$this->_iKey = bab_ZipIterator::EOF;
	}

	public function valid()
	{
		if(0 != $this->_iKey && bab_ZipIterator::EOF != $this->_iKey)
		{
			return true;
		}
		else if(0 == $this->_iKey)
		{
			$this->next();
			return $this->valid();
		}
		else if(bab_ZipIterator::EOF == $this->_iKey)
		{
			return false;
		}
		return false;
	}


	//Helper function
	
	public function setFullPathName($sFullPathName)
	{
		$this->_sFullPathName = $sFullPathName;
	}

	private function openZip()
	{
		if(function_exists('zip_open'))
		{
			$this->_oZip = zip_open($this->_sFullPathName);
		}
	}

	private function closeZip()
	{
		if(is_resource($this->_oZip))
		{
			zip_close($this->_oZip);
			$this->_oZip = null;
		}
	}
	
	private function getObject($oZipEntry)
	{
		return new bab_ZipEntry($this->_oZip, $oZipEntry);
	}
}
?>