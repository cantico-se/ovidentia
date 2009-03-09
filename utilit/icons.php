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
include_once 'base.php';


/**
 * Provides an icon theme.
 */
class Func_Icons extends bab_Functionality
{
	/**
	 * @return string
	 * @static
	 */
	public function getDescription()
	{
		return bab_translate('Provides an icon theme.');
	}


	/**
	 * Includes all necessary CSS files to the current page.
	 * 
	 * @return bool		false in case of error
	 */
	public function includeCss()
	{
		return true;
	}

	/**
	 * Returns the css file relative url corresponding to the icon theme. 
	 * 
	 * @return string
	 */
	public function getCss()
	{
		return '';
	}
}



/**
 * Provides the default icon theme.
 */
class Func_Icons_Default extends Func_Icons
{
	/**
	 * @return string
	 * @static
	 */
	public function getDescription()
	{
		return bab_translate('Provides the default icon theme.');
	}


	/**
	 * Includes all necessary CSS files to the current page.
	 * 
	 * @return bool		false in case of error
	 */
	public function includeCss()
	{
		global $babBody;
		$babBody->addStyleSheet('icons_default.css');
		return true;
	}

	/**
	 * Returns the css file relative url corresponding to the icon theme.
	 * 
	 * @return string
	 */
	public function getCss()
	{
		return $GLOBALS['babInstallPath'].'styles/icons_default.css';
	}
}

