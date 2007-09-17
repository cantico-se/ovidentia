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

DEFINE('BAB_EOF', -1);


class BAB_MySqlResultIterator
{
 	/**
	 * mysql resource
	 * 
	 * @access  private 
	 * @var  mysql resource
	 */
	var $_oResult = null;
	
 	/**
	 * 
	 * 
	 * @access  private 
	 * @var  mixed Used in the Iterator implementation
	 */
	var $_oObject = null;
	
 	/**
	 * Index position of the mysql resource
	 * 
	 * @access  private 
	 * @var  integer Used in the Iterator implementation
	 */
	var $_iKey = 0;

	
	function BAB_MySqlResultIterator()
	{
	}


	//Iterator interface function implementation

	/**
     * Return the current element 
     *
     * @return ATS_Contact The contact or null
     */
	function current()
	{
		if(is_null($this->_oObject))
		{
			return $this->next();
		}
		
		return $this->_oObject;
	}
	
	
	/**
     * Return the key of the current element  
     *
     * @return integer The key of the current element
     */
	function key()   
	{
		return $this->_iKey;
	}


	/**
     * Return the next element 
     *
     * @return mixed
     */
	function next()
	{
		global $babDB;
		if(false != ($aDatas = $babDB->db_fetch_assoc($this->_oResult)))
		{
			$this->_iKey++;
			$this->_oObject = $this->getObject($aDatas);
		}
		else 
		{
			$this->_oObject = null;
			$this->_iKey = BAB_EOF;
		}
		
		return $this->_oObject;
	}


	/**
     * Rewind the Iterator to the first element. 
     *
     */
	function rewind()
	{
		global $babDB;
		if ($babDB->db_num_rows($this->_oResult) > 0)
		{
			$babDB->db_data_seek($this->_oResult, 0);
		}
		$this->reset();
	}

	
	/**
	 * Place the Iterator on the $iRowNumberth element.
	 *@
	 */
	function seek($iRowNumber)
	{
		global $babDB;
		if ($babDB->db_num_rows($this->_oResult) > $iRowNumber)
		{
			$result = $babDB->db_data_seek($this->_oResult, $iRowNumber);
			$this->reset($iRowNumber);
		}
	}


	/**
     * Check if there is a current element after calls to rewind() or next(). 
     *
     * @return bool True if there is a current element after calls to rewind() or next()
    */
	function valid()
	{
		if(0 !== $this->_iKey && BAB_EOF !== $this->_iKey)
		{
			return true;
		}
		else if(0 === $this->_iKey)
		{
			$this->next();
			return $this->valid();
		}
		else if(BAB_EOF === $this->_iKey)
		{
			return false;
		}
		return false;
	}


	function count()
	{
		global $babDB;
		return $babDB->db_num_rows($this->_oResult);
	}


	//Helper function
	/**
     * Reset the contact and the key
     *
     */
	function reset($iRowNumber = 0)
	{
		$this->_oObject	= null;
		$this->_iKey	= $iRowNumber;
	}
	
	
	function setMySqlResult($oResult)
	{
		$this->_oResult = $oResult;
	}

	
	function getObject($aDatas)
	{
		return null;
	}
}
?>