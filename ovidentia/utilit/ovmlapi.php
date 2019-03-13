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

include_once $GLOBALS['babInstallPath'].'utilit/omlincl.php';

class bab_ovmlContainer
{
	var $handler = null;
	var $oapi = null;
	var $bdestroy = false;

	function bab_ovmlContainer(&$oapi, &$ctr)
	{
		$this->handler =& $ctr;
		$this->oapi =& $oapi;
	}

	function destroyContext()
	{
		if( !$this->bdestroy )
		{
		$this->oapi->_ovmlEngine->pop_ctx();
		$this->bdestroy = true;
		}
	}

	function getnext(&$obj)
	{
		if( !isset($obj) )
			{
			$obj =& $this;
			}

		if( $this->handler->getnext())
		{
			foreach($this->oapi->_ovmlEngine->get_variables($this->oapi->_ovmlEngine->get_currentContextname()) as $key => $val )
				{
				$obj->{$key} = $val;
				}
			return true;
		}
		else
		{
			$this->destroyContext();
			return false;
		}
	}
}


class bab_ovmlAPI
{
	var $_ovmlEngine;

	function bab_ovmlAPI($args = array())
	{
		$this->_ovmlEngine = new babOvTemplate($args);
	}

	/**
	 * Save variable in global context.
	 * @param string $name variable name
	 * @param mixed $value variable value
	 * @access public
	 */
	function putVar($name, $value)
	{
		$this->_ovmlEngine->bab_PutVar(array('name'=>$name, 'value' => $value));
	}


	/**
	 * Get variable
	 * @param string $name variable name
	 * @param array $param parameters to use for formating output
	 * @access public
	 */
	function getVar($name, $params=array())
	{
		$val = $this->_ovmlEngine->bab_GetVar(array('name'=>$name));
		if( count($params))
		{
			return $this->_ovmlEngine->format_output($val, $params);
		}
		return $val;
	}

	/**
	 * Get an instance of a container
	 * @param string $name container name
	 * @param array $param parameters to passe to container handler
	 * @access public
	 */
	function getContainer($name, $params=array())
	{
		$handler = $this->_ovmlEngine->handle_tag($name, '', $params, 'object');
		if( $handler ==  null )
		{
			return null;
		}
		return new bab_ovmlContainer($this, $handler);
	}

}
