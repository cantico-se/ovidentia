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


abstract class BAB_MySqlResultIterator implements SeekableIterator, Countable
{
	public	  $_oResult				= null;
	protected $_oObject				= null;
	protected $_iKey				= 0;
	protected $_oDataBaseAdapter	= null;
	
	const EOF = -1;
	
	function BAB_MySqlResultIterator($oDataBaseAdapter = null)
	{
		if(!isset($oDataBaseAdapter))
		{
			global $babDB;
			$oDataBaseAdapter = $babDB;
		}
		$this->setDataBaseAdapter($oDataBaseAdapter);
	}
	
	
	//Helper function

	function setDataBaseAdapter($oDataBaseAdapter)
	{
		$this->_oDataBaseAdapter = $oDataBaseAdapter;
	}
	
	function getDataBaseAdapter()
	{
		return $this->_oDataBaseAdapter;
	}

	
	//Iterator interface function implementation

	/**
     * Return the current element 
     *
     * @return 
     */
	public function current()
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
		$this->executeQuery();
	
		if(false !== ($aDatas = $this->getDataBaseAdapter()->db_fetch_assoc($this->_oResult)))
		{
			$this->_iKey++;
			$this->_oObject = $this->getObject($aDatas);
		}
		else 
		{
			$this->_oObject = null;
			$this->_iKey = self::EOF;
		}
		
		return $this->_oObject;
	}

	/**
     * Rewind the Iterator to the first element. 
     *
     */
	function rewind()
	{
		$this->executeQuery();
		if($this->getDataBaseAdapter()->db_num_rows($this->_oResult) > 0)
		{
			$this->getDataBaseAdapter()->db_data_seek($this->_oResult, 0);
		}
		$this->reset();
	}

	/**
     * Check if there is a current element after calls to rewind() or next(). 
     *
     * @return bool True if there is a current element after calls to rewind() or next()
    */
	function valid()
	{
		if(0 !== $this->_iKey && self::EOF !== $this->_iKey)
		{
			return true;
		}
		else if(0 === $this->_iKey)
		{
			$this->next();
			return $this->valid();
		}
		else if(self::EOF === $this->_iKey)
		{
			return false;
		}
		return false;
	}
	
	
	//SeekableIterator interface function implementation

	/**
	 * Place the Iterator on the $iRowNumberth element.
	 *
	 */
	function seek($iRowNumber)
	{
		$this->executeQuery();
		if($this->getDataBaseAdapter()->db_num_rows($this->_oResult) > $iRowNumber)
		{
			$result = $this->getDataBaseAdapter()->db_data_seek($this->_oResult, $iRowNumber);
			$this->reset($iRowNumber);
		}
	}
	
	
	//Countable interface function implementation
	
	function count()
	{
		$this->executeQuery();
		return $this->getDataBaseAdapter()->db_num_rows($this->_oResult);
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

	//Hack
	function executeQuery()
	{
	
	}
	
	abstract public function getObject($aDatas);
}


/**
 * 
 *
 */
abstract class bab_MySqlIterator extends BAB_MySqlResultIterator
{
	protected $sQuery		= null;
	protected $oCriteria	= null;
	protected $aOrder		= array();
	protected $aGroupBy		= array();

	public function __construct($oDataBaseAdapter = null)
	{
		parent::BAB_MySqlResultIterator($oDataBaseAdapter);
	}

	public function setQuery($sQuery)
	{
		$this->sQuery = $sQuery;
		return $this;
	}
	
	public function orderAsc($sField)
	{
		$this->aOrder[$sField] = 'ASC';
		return $this;
	}

	public function orderDesc($sField)
	{
		$this->aOrder[$sField] = 'DESC';
		return $this;
	}

	function processOrder()
	{
		$sOrder = '';
		if(count($this->aOrder) > 0)
		{
			$aValue = array();
			foreach($this->aOrder as $sField => $sOrder)
			{
				$aValue[] = $sField . ' ' . $sOrder;
			}
			$sOrder = 'ORDER BY ' . implode(', ', $aValue);
		}
		return $sOrder;
	}
	
	public function setCriteria(BAB_Criteria $oCriteria = null)
	{
		$this->oCriteria = $oCriteria;
		return $this;
	}
	
	public function getCriteria()
	{
		if(is_null($this->oCriteria))
		{
			$this->oCriteria = new BAB_Criteria();
		}
		return $this->oCriteria;
	}
	
	function executeQuery()
	{
		if(is_null($this->_oResult))
		{
			$sWhereClause	= '';
			$sCriteria		= $this->getCriteria()->toString();
			if('' != $sCriteria)
			{
				$sWhereClause = 'WHERE ' . $sCriteria;
			}
			$sQuery	= $this->sQuery . ' ' . $sWhereClause . ' ' . $this->processOrder();
			// bab_debug($sQuery);
			$this->setMySqlResult($this->getDataBaseAdapter()->db_query($sQuery));
		}
	}
	
	public function clear()
	{
		$this->_oObject		= null;
		$this->_oResult		= null;
		$this->_iKey		= 0;
		$this->sQuery		= null;
		$this->oCriteria	= null;
		$this->aOrder		= array();
		$this->aGroupBy		= array();
	}
}


/**
 * Simple query iterator
 *
 */
class bab_QueryIterator extends BAB_MySqlResultIterator
{
	protected $sQuery = null;
	
	public function setQuery($sQuery)
	{
		$this->sQuery = $sQuery;
		return $this;
	}
	
	
	public function getObject($aDatas)
	{
		return $aDatas;
	}
	
	function executeQuery()
	{
		if(is_null($this->_oResult))
		{
			$this->setMySqlResult($this->getDataBaseAdapter()->db_query($this->sQuery));
		}
	}
}
